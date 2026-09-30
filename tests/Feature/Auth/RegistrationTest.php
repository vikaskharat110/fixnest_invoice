<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Sms\SmsProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeSmsProvider;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registering_sends_an_otp_and_does_not_create_the_user_yet(): void
    {
        $fake = new FakeSmsProvider;
        $this->app->instance(SmsProvider::class, $fake);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '919876543210',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('register.verify'));
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
        $this->assertCount(1, $fake->sent);
        $this->assertStringContainsString('mobile verification', $fake->sent[0]['message']);
    }

    public function test_new_users_can_register_after_verifying_the_otp(): void
    {
        $fake = new FakeSmsProvider;
        $this->app->instance(SmsProvider::class, $fake);

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '919876543210',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response = $this->post('/register/verify', ['otp' => $fake->lastCode()]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('919876543210', $user->phone);
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_registration_fails_with_the_wrong_otp(): void
    {
        $fake = new FakeSmsProvider;
        $this->app->instance(SmsProvider::class, $fake);

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '919876543210',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response = $this->post('/register/verify', ['otp' => '000000']);

        $response->assertSessionHasErrors('otp');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_requires_a_unique_phone_number(): void
    {
        User::factory()->create(['phone' => '919876543210']);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '919876543210',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('phone');
    }
}

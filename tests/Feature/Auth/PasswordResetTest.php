<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Sms\SmsProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\FakeSmsProvider;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_request_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_otp_can_be_requested_for_a_registered_phone(): void
    {
        $fake = new FakeSmsProvider;
        $this->app->instance(SmsProvider::class, $fake);

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['phone' => $user->phone]);

        $response->assertRedirect(route('password.otp.verify'));
        $this->assertCount(1, $fake->sent);
        $this->assertStringContainsString('password reset', $fake->sent[0]['message']);
    }

    public function test_reset_otp_is_rejected_for_an_unregistered_phone(): void
    {
        $fake = new FakeSmsProvider;
        $this->app->instance(SmsProvider::class, $fake);

        $response = $this->post('/forgot-password', ['phone' => '919999999999']);

        $response->assertSessionHasErrors('phone');
        $this->assertCount(0, $fake->sent);
    }

    public function test_password_can_be_reset_after_verifying_the_otp(): void
    {
        $fake = new FakeSmsProvider;
        $this->app->instance(SmsProvider::class, $fake);

        $user = User::factory()->create();

        $this->post('/forgot-password', ['phone' => $user->phone]);
        $this->post('/forgot-password/verify', ['otp' => $fake->lastCode()]);

        $response = $this->post('/reset-password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_reset_password_screen_redirects_without_a_verified_otp(): void
    {
        $response = $this->get('/reset-password');

        $response->assertRedirect(route('password.request'));
    }
}

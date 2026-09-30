<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Sms\SmsProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeSmsProvider;
use Tests\TestCase;

class LoginOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_can_be_sent_to_a_registered_phone(): void
    {
        $fake = new FakeSmsProvider;
        $this->app->instance(SmsProvider::class, $fake);

        $user = User::factory()->create();

        $response = $this->post('/login/otp/send', ['phone' => $user->phone]);

        $response->assertRedirect(route('login.otp.verify'));
        $this->assertCount(1, $fake->sent);
    }

    public function test_otp_send_is_rejected_for_an_unregistered_phone(): void
    {
        $fake = new FakeSmsProvider;
        $this->app->instance(SmsProvider::class, $fake);

        $response = $this->post('/login/otp/send', ['phone' => '919999999999']);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_user_can_log_in_with_a_valid_otp(): void
    {
        $fake = new FakeSmsProvider;
        $this->app->instance(SmsProvider::class, $fake);

        $user = User::factory()->create();

        $this->post('/login/otp/send', ['phone' => $user->phone]);
        $response = $this->post('/login/otp/verify', ['otp' => $fake->lastCode()]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_fails_with_the_wrong_otp(): void
    {
        $fake = new FakeSmsProvider;
        $this->app->instance(SmsProvider::class, $fake);

        $user = User::factory()->create();

        $this->post('/login/otp/send', ['phone' => $user->phone]);
        $response = $this->post('/login/otp/verify', ['otp' => '000000']);

        $response->assertSessionHasErrors('otp');
        $this->assertGuest();
    }
}

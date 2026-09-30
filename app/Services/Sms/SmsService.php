<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function __construct(private readonly SmsProvider $provider)
    {
    }

    /**
     * Builds the DLT-approved template text for the given purpose, with the
     * OTP code dropped into the {#var#} slot. Text must match the carrier's
     * approved template verbatim (aside from the code) or the SMS is
     * silently scrubbed even though the gateway reports success.
     *
     * @throws SmsException
     */
    public function sendOtp(string $phone, string $code, string $purpose): void
    {
        if (! config('services.bulksmsconnect.enabled')) {
            Log::info('SMS disabled (SMS_ENABLED=false) — skipping OTP send', ['phone' => $phone, 'purpose' => $purpose]);

            return;
        }

        $message = $this->otpMessage($code, $purpose);

        $result = $this->provider->sendSms($phone, $message);

        Log::info('OTP SMS submitted to provider', [
            'phone' => $phone,
            'purpose' => $purpose,
            'providerMessageId' => $result->providerMessageId,
        ]);
    }

    private function otpMessage(string $code, string $purpose): string
    {
        return match ($purpose) {
            'password_reset' => "Dear user, your OTP for password reset is {$code}. Team PAYBRIDGE",
            default => "Dear user, your OTP for mobile verification is {$code}. Team PAYBRIDGE",
        };
    }
}

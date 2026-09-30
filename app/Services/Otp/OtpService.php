<?php

namespace App\Services\Otp;

use App\Models\OtpCode;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OtpService
{
    private const TTL_MINUTES = 5;

    private const RESEND_COOLDOWN_SECONDS = 60;

    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly SmsService $sms)
    {
    }

    /**
     * Sends a fresh OTP for the given phone/purpose. The SMS is sent before
     * the code is persisted, so a failed send doesn't burn the resend cooldown.
     *
     * @throws ValidationException
     * @throws \App\Services\Sms\SmsException
     */
    public function issue(string $phone, string $purpose): void
    {
        $lastSentAt = OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->latest('created_at')
            ->value('created_at');

        $secondsSinceLastSend = $lastSentAt ? $lastSentAt->diffInSeconds(now(), true) : null;

        if ($secondsSinceLastSend !== null && $secondsSinceLastSend < self::RESEND_COOLDOWN_SECONDS) {
            $wait = (int) ceil(self::RESEND_COOLDOWN_SECONDS - $secondsSinceLastSend);

            throw ValidationException::withMessages([
                'phone' => "Please wait {$wait}s before requesting another code.",
            ]);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->sms->sendOtp($phone, $code, $purpose);

        OtpCode::create([
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function verify(string $phone, string $purpose, string $code): void
    {
        $record = OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('created_at')
            ->first();

        if (! $record || $record->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => 'This code has expired or is invalid. Please request a new one.',
            ]);
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages([
                'otp' => 'Too many incorrect attempts. Please request a new code.',
            ]);
        }

        if (! Hash::check($code, $record->code_hash)) {
            $record->increment('attempts');

            throw ValidationException::withMessages([
                'otp' => 'Incorrect code.',
            ]);
        }

        $record->update(['consumed_at' => now()]);
    }
}

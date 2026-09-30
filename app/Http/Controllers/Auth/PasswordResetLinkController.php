<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Sms\SmsException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    private const OTP_PURPOSE = 'password_reset';

    private const SESSION_PHONE_KEY = 'password_reset.phone';

    public function __construct(private readonly OtpService $otp)
    {
    }

    /**
     * Display the "enter your phone number" screen.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send (or resend) a password-reset OTP to the given phone number.
     */
    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
        ]);

        if (! User::where('phone', $data['phone'])->exists()) {
            return back()->withInput()->withErrors(['phone' => 'No account was found with that phone number.']);
        }

        try {
            $this->otp->issue($data['phone'], self::OTP_PURPOSE);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (SmsException $e) {
            return back()->withInput()->withErrors(['phone' => 'We could not send a code right now. Please try again shortly.']);
        }

        $request->session()->put(self::SESSION_PHONE_KEY, $data['phone']);

        return redirect()->route('password.otp.verify');
    }

    /**
     * Display the "enter the OTP" screen for password reset.
     */
    public function showVerify(Request $request): RedirectResponse|View
    {
        $phone = $request->session()->get(self::SESSION_PHONE_KEY);

        if (! $phone) {
            return redirect()->route('password.request');
        }

        return view('auth.forgot-password-verify-otp', ['phone' => $phone]);
    }

    /**
     * Verify the OTP and, on success, allow the user through to the
     * new-password screen for a short window.
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $phone = $request->session()->get(self::SESSION_PHONE_KEY);

        if (! $phone) {
            return redirect()->route('password.request');
        }

        $data = $request->validate(['otp' => ['required', 'string']]);

        try {
            $this->otp->verify($phone, self::OTP_PURPOSE, $data['otp']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $request->session()->put('password_reset.verified_phone', $phone);
        $request->session()->put('password_reset.verified_until', now()->addMinutes(10));
        $request->session()->forget(self::SESSION_PHONE_KEY);

        return redirect()->route('password.reset');
    }
}

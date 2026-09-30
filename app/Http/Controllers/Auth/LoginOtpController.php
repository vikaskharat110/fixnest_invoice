<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Sms\SmsException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginOtpController extends Controller
{
    private const OTP_PURPOSE = 'login';

    private const SESSION_KEY = 'login_otp.phone';

    public function __construct(private readonly OtpService $otp)
    {
    }

    /**
     * Send (or resend) a login OTP to the given phone number.
     */
    public function send(Request $request): RedirectResponse
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

        $request->session()->put(self::SESSION_KEY, $data['phone']);

        return redirect()->route('login.otp.verify');
    }

    /**
     * Display the "enter the OTP" screen for OTP login.
     */
    public function show(Request $request): RedirectResponse|View
    {
        $phone = $request->session()->get(self::SESSION_KEY);

        if (! $phone) {
            return redirect()->route('login');
        }

        return view('auth.login-verify-otp', ['phone' => $phone]);
    }

    /**
     * Verify the OTP and, on success, log the matching user in.
     */
    public function verify(Request $request): RedirectResponse
    {
        $phone = $request->session()->get(self::SESSION_KEY);

        if (! $phone) {
            return redirect()->route('login');
        }

        $data = $request->validate(['otp' => ['required', 'string']]);

        try {
            $this->otp->verify($phone, self::OTP_PURPOSE, $data['otp']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            return redirect()->route('login')->withErrors(['phone' => 'No account was found with that phone number.']);
        }

        Auth::login($user);

        $request->session()->regenerate();
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}

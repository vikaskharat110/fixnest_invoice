<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Sms\SmsException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    private const OTP_PURPOSE = 'register';

    private const SESSION_KEY = 'registration.pending';

    public function __construct(private readonly OtpService $otp)
    {
    }

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Validate the registration form, then send an OTP to the phone number
     * and stash the pending registration details in the session until the
     * code is verified.
     */
    public function sendOtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'regex:/^91\d{10}$/', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'phone.regex' => 'Enter a valid Indian mobile number with country code, e.g. 91XXXXXXXXXX.',
        ]);

        try {
            $this->otp->issue($data['phone'], self::OTP_PURPOSE);
        } catch (SmsException $e) {
            return back()->withInput()->withErrors(['phone' => 'We could not send a verification code right now. Please try again shortly.']);
        }

        $request->session()->put(self::SESSION_KEY, [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('register.verify');
    }

    /**
     * Display the "enter the OTP" screen for the pending registration.
     */
    public function showVerify(Request $request): RedirectResponse|View
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! $pending) {
            return redirect()->route('register');
        }

        return view('auth.register-verify-otp', ['phone' => $pending['phone']]);
    }

    /**
     * Re-send the OTP for the pending registration (subject to the same
     * per-phone cooldown as the initial send).
     */
    public function resendOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! $pending) {
            return redirect()->route('register');
        }

        try {
            $this->otp->issue($pending['phone'], self::OTP_PURPOSE);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (SmsException $e) {
            return back()->withErrors(['otp' => 'We could not resend a verification code right now. Please try again shortly.']);
        }

        return back()->with('status', 'A new code has been sent.');
    }

    /**
     * Verify the OTP and, on success, create the account and log the user in.
     *
     * @throws ValidationException
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! $pending) {
            return redirect()->route('register');
        }

        $data = $request->validate(['otp' => ['required', 'string']]);

        try {
            $this->otp->verify($pending['phone'], self::OTP_PURPOSE, $data['otp']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $user = User::create([
            'name' => $pending['name'],
            'email' => $pending['email'],
            'phone' => $pending['phone'],
            'phone_verified_at' => now(),
            'password' => $pending['password'],
        ]);

        if (! Role::where('name', 'admin')->exists()) {
            $user->assignRole(Role::findOrCreate('admin', 'web'));
        }

        $request->session()->forget(self::SESSION_KEY);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}

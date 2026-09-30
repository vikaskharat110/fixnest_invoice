<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the new-password view, provided the phone number was OTP
     * verified within the last few minutes.
     */
    public function create(Request $request): RedirectResponse|View
    {
        $phone = $this->verifiedPhone($request);

        if (! $phone) {
            return redirect()->route('password.request')->withErrors([
                'phone' => 'Please verify your phone number again before setting a new password.',
            ]);
        }

        return view('auth.reset-password', ['phone' => $phone]);
    }

    /**
     * Set the new password for the OTP-verified phone number.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $phone = $this->verifiedPhone($request);

        if (! $phone) {
            return redirect()->route('password.request')->withErrors([
                'phone' => 'Please verify your phone number again before setting a new password.',
            ]);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::where('phone', $phone)->first();

        if (! $user) {
            return redirect()->route('password.request')->withErrors([
                'phone' => 'No account was found with that phone number.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        $request->session()->forget(['password_reset.verified_phone', 'password_reset.verified_until']);

        return redirect()->route('login')->with('status', __('Your password has been reset.'));
    }

    private function verifiedPhone(Request $request): ?string
    {
        $phone = $request->session()->get('password_reset.verified_phone');
        $until = $request->session()->get('password_reset.verified_until');

        if (! $phone || ! $until || now()->greaterThan($until)) {
            return null;
        }

        return $phone;
    }
}

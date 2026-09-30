<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-5 text-base" :status="session('status')" />

    <div x-data="{ tab: 'password' }">
        <!-- Tabs -->
        <div class="flex mb-6 rounded-lg bg-canvas p-1 text-sm font-medium">
            <button type="button" @click="tab = 'password'"
                    :class="tab === 'password' ? 'bg-surface shadow text-ink' : 'text-ink-muted'"
                    class="flex-1 rounded-md py-2 transition">
                {{ __('Password') }}
            </button>
            <button type="button" @click="tab = 'otp'"
                    :class="tab === 'otp' ? 'bg-surface shadow text-ink' : 'text-ink-muted'"
                    class="flex-1 rounded-md py-2 transition">
                {{ __('Login with OTP') }}
            </button>
        </div>

        <!-- Password login -->
        <form x-show="tab === 'password'" method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" class="text-base" />
                <x-text-input id="email" class="block mt-1.5 w-full text-base py-3" type="email" name="email" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div x-data="{ showPassword: false }">
                <x-input-label for="password" :value="__('Password')" class="text-base" />

                <div class="relative mt-1.5">
                    <x-text-input id="password" class="block w-full text-base py-3 pr-11"
                                    type="password"
                                    x-bind:type="showPassword ? 'text' : 'password'"
                                    name="password"
                                    required autocomplete="current-password" />

                    <button type="button" @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-muted hover:text-ink transition"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'" tabindex="-1">
                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.01 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.01-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-border text-signal shadow-sm focus:ring-1 focus:ring-signal focus:ring-offset-0" name="remember">
                    <span class="ms-2 text-base text-ink-muted">{{ __('Remember me') }}</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="text-base text-signal hover:text-signal-hover transition" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <x-primary-button class="w-full justify-center py-3 text-base">
                {{ __('Log in') }}
            </x-primary-button>
        </form>

        <!-- OTP login -->
        <form x-show="tab === 'otp'" x-cloak method="POST" action="{{ route('login.otp.send') }}" class="space-y-6">
            @csrf

            <div>
                <x-input-label for="otp_phone" :value="__('Mobile Number')" class="text-base" />
                <x-text-input id="otp_phone" class="block mt-1.5 w-full text-base py-3" type="tel" name="phone"
                              placeholder="91XXXXXXXXXX" :value="old('phone')" autocomplete="tel" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                <p class="mt-1 text-sm text-ink-muted">{{ __('Enter your registered mobile number with country code, e.g. 91XXXXXXXXXX.') }}</p>
            </div>

            <x-primary-button class="w-full justify-center py-3 text-base">
                {{ __('Send OTP') }}
            </x-primary-button>
        </form>
    </div>

    <p class="mt-8 text-center text-base text-ink-muted">
        Need access? <a href="{{ route('public.contact') }}" class="text-signal hover:text-signal-hover transition">Contact your administrator</a>.
    </p>
</x-guest-layout>

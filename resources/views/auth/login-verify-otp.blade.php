<x-guest-layout>
    <x-auth-session-status class="mb-5 text-base" :status="session('status')" />

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Enter the code sent to :phone.', ['phone' => $phone]) }}
    </div>

    <form method="POST" action="{{ route('login.otp.verify.store') }}">
        @csrf

        <div>
            <x-input-label for="otp" :value="__('Verification Code')" />
            <x-text-input id="otp" class="block mt-1 w-full" type="text" name="otp" inputmode="numeric"
                          autocomplete="one-time-code" required autofocus />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Verify & Log in') }}
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('login.otp.send') }}" class="mt-4">
        @csrf
        <input type="hidden" name="phone" value="{{ $phone }}">
        <button type="submit" class="text-sm text-signal hover:text-signal-hover transition underline">
            {{ __('Resend code') }}
        </button>
    </form>
</x-guest-layout>

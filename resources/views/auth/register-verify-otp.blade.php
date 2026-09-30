<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Enter the code sent to :phone to finish creating your account.', ['phone' => $phone]) }}
    </div>

    <form method="POST" action="{{ route('register.verify.store') }}">
        @csrf

        <div>
            <x-input-label for="otp" :value="__('Verification Code')" />
            <x-text-input id="otp" class="block mt-1 w-full" type="text" name="otp" inputmode="numeric"
                          autocomplete="one-time-code" required autofocus />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Verify & Create Account') }}
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('register.verify.resend') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-sm underline text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
            {{ __('Resend code') }}
        </button>
    </form>
</x-guest-layout>

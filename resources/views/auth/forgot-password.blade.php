<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Forgot your password? Enter the mobile number registered on your account and we will text you a code to reset it.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.otp.send') }}">
        @csrf

        <!-- Mobile Number -->
        <div>
            <x-input-label for="phone" :value="__('Mobile Number')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" placeholder="91XXXXXXXXXX"
                          :value="old('phone')" required autofocus autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Send Reset Code') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

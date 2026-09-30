<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    @php
        $showDetailsTab = session('status') === 'organization-updated'
            || $errors->hasAny(['org_name', 'org_address', 'org_gst_number', 'org_pan_number', 'org_bank_details', 'org_invoice_prefix', 'org_logo', 'org_signature']);
    @endphp

    <div class="py-8" x-data="{ tab: '{{ $showDetailsTab ? 'details' : 'profile' }}' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if ($organization)
                <div class="inline-flex rounded-lg border border-border p-1 bg-canvas">
                    <button type="button" @click="tab = 'profile'"
                            class="px-4 py-2 text-sm font-medium rounded-md transition"
                            :class="tab === 'profile' ? 'bg-surface text-ink shadow-sm' : 'text-ink-muted hover:text-ink'">
                        {{ __('Profile') }}
                    </button>
                    <button type="button" @click="tab = 'details'"
                            class="px-4 py-2 text-sm font-medium rounded-md transition"
                            :class="tab === 'details' ? 'bg-surface text-ink shadow-sm' : 'text-ink-muted hover:text-ink'">
                        {{ __('Details') }}
                    </button>
                </div>
            @endif

            <div x-show="tab === 'profile'" x-cloak class="space-y-6">
                <div class="p-4 sm:p-8 bg-surface border border-border rounded-xl shadow-sm shadow-black/[0.03]">
                    <div class="max-w-xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-surface border border-border rounded-xl shadow-sm shadow-black/[0.03]">
                    <div class="max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-surface border border-border rounded-xl shadow-sm shadow-black/[0.03]">
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>

            @if ($organization)
                <div x-show="tab === 'details'" x-cloak>
                    <div class="p-4 sm:p-8 bg-surface border border-border rounded-xl shadow-sm shadow-black/[0.03]">
                        <div class="max-w-xl">
                            @include('profile.partials.update-organization-form')
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

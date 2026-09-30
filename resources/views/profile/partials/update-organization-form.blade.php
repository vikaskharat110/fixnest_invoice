<section>
    <header>
        <h2 class="text-sm font-semibold text-ink">
            {{ __('Details') }}
        </h2>

        <p class="mt-1 text-xs text-ink-muted">
            {{ __('This is the issuing business shown on every invoice you send.') }}
        </p>
    </header>

    <form method="post" action="{{ route('organization.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf

        <div>
            <x-input-label for="org_name" :value="__('Company Name')" />
            <x-text-input id="org_name" name="org_name" type="text" class="mt-1 block w-full" :value="old('org_name', $organization->name)" />
            <x-input-error class="mt-2" :messages="$errors->get('org_name')" />
        </div>

        <div>
            <x-input-label for="org_address" :value="__('Address')" />
            <textarea id="org_address" name="org_address" rows="3"
                      class="mt-1 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">{{ old('org_address', $organization->address) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('org_address')" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <x-input-label for="org_gst_number" :value="__('GSTIN')" />
                <x-text-input id="org_gst_number" name="org_gst_number" type="text" class="mt-1 block w-full font-mono" :value="old('org_gst_number', $organization->gst_number)" />
                <x-input-error class="mt-2" :messages="$errors->get('org_gst_number')" />
            </div>
            <div>
                <x-input-label for="org_pan_number" :value="__('PAN')" />
                <x-text-input id="org_pan_number" name="org_pan_number" type="text" class="mt-1 block w-full font-mono" :value="old('org_pan_number', $organization->pan_number)" />
                <x-input-error class="mt-2" :messages="$errors->get('org_pan_number')" />
            </div>
        </div>

        <div>
            <x-input-label for="org_support_email" :value="__('Support Email')" />
            <x-text-input id="org_support_email" name="org_support_email" type="email" class="mt-1 block w-full" :value="old('org_support_email', $organization->support_email)" />
            <p class="mt-1.5 text-xs text-ink-muted">{{ __('Shown on every invoice PDF for customers to reach out to.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('org_support_email')" />
        </div>

        <div>
            <x-input-label for="org_bank_details" :value="__('Bank Details')" />
            <textarea id="org_bank_details" name="org_bank_details" rows="4" placeholder="Bank name, account number, IFSC, branch..."
                      class="mt-1 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">{{ old('org_bank_details', $organization->bank_details) }}</textarea>
            <p class="mt-1.5 text-xs text-ink-muted">{{ __('Displayed in the payment details block on invoices.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('org_bank_details')" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <x-input-label for="org_logo" :value="__('Logo')" />
                @if ($organization->logo_path)
                    <div class="mt-1.5 mb-2 flex items-center gap-3">
                        <img src="{{ Storage::url($organization->logo_path) }}" class="h-12 w-12 object-contain rounded-lg border border-border bg-canvas p-1">
                        <span class="text-xs text-ink-muted">{{ __('Current logo') }}</span>
                    </div>
                @endif
                <input id="org_logo" name="org_logo" type="file" accept="image/*"
                       class="block w-full text-sm text-ink-muted
                              file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border file:border-border file:bg-canvas file:text-ink file:text-sm file:font-medium
                              hover:file:bg-border/40 file:transition" />
                <x-input-error class="mt-2" :messages="$errors->get('org_logo')" />
            </div>
            <div>
                <x-input-label for="org_signature" :value="__('Signature')" />
                @if ($organization->signature_path)
                    <div class="mt-1.5 mb-2 flex items-center gap-3">
                        <img src="{{ Storage::url($organization->signature_path) }}" class="h-12 w-12 object-contain rounded-lg border border-border bg-canvas p-1">
                        <span class="text-xs text-ink-muted">{{ __('Current signature') }}</span>
                    </div>
                @endif
                <input id="org_signature" name="org_signature" type="file" accept="image/*"
                       class="block w-full text-sm text-ink-muted
                              file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border file:border-border file:bg-canvas file:text-ink file:text-sm file:font-medium
                              hover:file:bg-border/40 file:transition" />
                <x-input-error class="mt-2" :messages="$errors->get('org_signature')" />
            </div>
        </div>

        <div>
            <x-input-label for="org_invoice_prefix" :value="__('Invoice Prefix')" />
            <x-text-input id="org_invoice_prefix" name="org_invoice_prefix" type="text" class="mt-1 block w-full max-w-xs font-mono uppercase" :value="old('org_invoice_prefix', $organization->invoice_prefix)" />
            <p class="mt-1.5 text-xs text-ink-muted">{{ __('One sequence for every invoice and sales order you create, e.g.') }} <span class="font-mono">ACME/2026/0001</span></p>
            <x-input-error class="mt-2" :messages="$errors->get('org_invoice_prefix')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'organization-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-ink-muted"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>

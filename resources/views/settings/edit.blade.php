<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">Settings</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 px-4 py-3 bg-status-paid/10 border border-status-paid/20 text-status-paid text-sm rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('settings.update') }}" class="bg-surface border border-border rounded-xl p-6 space-y-6">
                @csrf

                <div>
                    <h3 class="text-sm font-semibold text-ink mb-4">General</h3>
                    <x-input-label value="App Name" />
                    <input type="text" name="app_name" value="{{ old('app_name', $settings['app_name']) }}"
                           class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                    @error('app_name') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                </div>

                <div class="border-t border-border pt-6">
                    <h3 class="text-sm font-semibold text-ink mb-4">Invoice Numbering</h3>
                    <x-input-label value="Numbering Reset" />
                    <select name="invoice_numbering_reset" class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                        <option value="financial_year" @selected(old('invoice_numbering_reset', $settings['invoice_numbering_reset']) === 'financial_year')>Financial Year (resets 1 April — recommended for India)</option>
                        <option value="calendar_year" @selected(old('invoice_numbering_reset', $settings['invoice_numbering_reset']) === 'calendar_year')>Calendar Year (resets 1 January)</option>
                        <option value="never" @selected(old('invoice_numbering_reset', $settings['invoice_numbering_reset']) === 'never')>Never (keeps counting forever)</option>
                    </select>
                    <p class="mt-1.5 text-xs text-ink-muted">Applies to every company's next new invoice. Existing invoice numbers are never changed.</p>
                    @error('invoice_numbering_reset') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror

                    <div class="mt-5">
                        <x-input-label value="Starting Invoice Number" />
                        <input type="number" name="invoice_start_number" min="1" max="99999999" step="1"
                               value="{{ old('invoice_start_number', $settings['invoice_start_number']) }}"
                               @readonly($sequenceStarted)
                               class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5 font-mono read-only:bg-canvas read-only:text-ink-muted read-only:cursor-not-allowed">
                        <p class="mt-1.5 text-xs text-ink-muted">Set the number from which invoice numbering should begin. Default is 1.</p>
                        @if ($sequenceStarted)
                            <p class="mt-1.5 text-xs text-status-pending">Locked — invoice numbering has already started (next number: {{ $nextInvoiceNumber }}).</p>
                        @else
                            <p class="mt-1.5 text-xs text-ink-muted">Can only be set before the first invoice is generated. Applies to the first numbering sequence; later yearly resets start again at 1.</p>
                        @endif
                        @error('invoice_start_number') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="border-t border-border pt-6">
                    <h3 class="text-sm font-semibold text-ink mb-4">PDF Options</h3>
                    <div class="space-y-3">
                        <label class="flex items-center gap-2 text-sm text-ink">
                            <input type="hidden" name="pdf_show_logo" value="0">
                            <input type="checkbox" name="pdf_show_logo" value="1" @checked(old('pdf_show_logo', $settings['pdf_show_logo']) == '1')
                                   class="rounded border-border text-signal focus:ring-signal">
                            Show company logo on invoices
                        </label>
                        <label class="flex items-center gap-2 text-sm text-ink">
                            <input type="hidden" name="pdf_show_signature" value="0">
                            <input type="checkbox" name="pdf_show_signature" value="1" @checked(old('pdf_show_signature', $settings['pdf_show_signature']) == '1')
                                   class="rounded border-border text-signal focus:ring-signal">
                            Show signature on invoices
                        </label>
                    </div>
                    <div class="mt-4">
                        <x-input-label value="Footer Note" />
                        <input type="text" name="pdf_footer_note" value="{{ old('pdf_footer_note', $settings['pdf_footer_note']) }}"
                               class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                        <p class="mt-1.5 text-xs text-ink-muted">Printed at the bottom of every invoice, e.g. "Thank you for your business!"</p>
                        @error('pdf_footer_note') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <button type="submit" class="px-4 py-2.5 bg-signal text-white text-sm font-medium rounded-lg hover:bg-signal-hover transition">
                        Save Settings
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
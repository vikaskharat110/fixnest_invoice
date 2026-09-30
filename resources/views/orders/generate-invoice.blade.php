<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('orders.show', $order) }}"
               class="inline-flex items-center gap-1.5 text-xs font-medium text-ink-muted hover:text-ink transition mb-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ $order->order_number }}
            </a>
            <h2 class="font-semibold text-xl text-ink leading-tight">Generate Next Invoice</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @if ($draftInvoices->isNotEmpty())
                <div class="px-4 py-3 bg-status-pending/10 border border-status-pending/20 text-status-pending text-sm rounded-lg">
                    <p class="font-medium mb-1">Check the existing draft invoice{{ $draftInvoices->count() === 1 ? '' : 's' }} on this order first</p>
                    <p>A draft doesn't count towards "Already Billed" below, so it's easy to lose track of. Finalize or delete it before generating another invoice, to avoid over-committing this order.</p>
                    <ul class="mt-2 space-y-1">
                        @foreach ($draftInvoices as $draft)
                            <li>
                                <a href="{{ route('invoices.show', $draft) }}" class="font-mono font-medium underline hover:no-underline">
                                    {{ $draft->invoice_number }}
                                </a>
                                — &#8377;{{ number_format($draft->total_amount, 2) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-surface border border-border rounded-xl shadow-sm shadow-black/[0.03] p-6 space-y-5">

                <div class="grid grid-cols-3 gap-4 pb-5 border-b border-border">
                    <div>
                        <p class="text-xs text-ink-muted">Sales Order Amount</p>
                        <p class="text-base font-semibold text-ink mt-0.5 font-mono">&#8377;{{ number_format($order->total_value, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted">Already Billed</p>
                        <p class="text-base font-semibold text-signal mt-0.5 font-mono">&#8377;{{ number_format($order->amount_billed, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted">Remaining</p>
                        <p class="text-base font-semibold text-status-pending mt-0.5 font-mono">&#8377;{{ number_format($order->amount_remaining, 2) }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('orders.generate-invoice.store', $order) }}">
                    @csrf

                    <div>
                        <x-input-label for="amount" value="Invoice Amount (₹, incl. GST)" />
                        <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01"
                                      max="{{ $order->amount_remaining }}"
                                      class="mt-1.5 font-mono"
                                      value="{{ old('amount') }}" required autofocus />
                        @error('amount') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-xs text-ink-muted">
                            Must be greater than ₹0 and cannot exceed the remaining amount of &#8377;{{ number_format($order->amount_remaining, 2) }}.
                        </p>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('orders.show', $order) }}" class="px-4 py-2.5 text-sm font-medium text-ink-muted hover:text-ink transition">
                            Cancel
                        </a>
                        <x-primary-button>Generate Invoice</x-primary-button>
                    </div>
                </form>

            </div>

        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <a href="{{ route('orders.index') }}"
                   class="inline-flex items-center gap-1.5 text-xs font-medium text-ink-muted hover:text-ink transition mb-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Sales Orders
                </a>
                <h2 class="font-semibold text-xl text-ink leading-tight font-mono">{{ $order->order_number }}</h2>
            </div>
            <a href="{{ route('orders.edit', $order) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-ink text-white text-sm font-medium rounded-lg hover:bg-ink/90 transition">
                Edit Order
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="px-4 py-3 bg-status-paid/10 border border-status-paid/20 text-status-paid text-sm rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Order details --}}
            <div class="bg-surface border border-border rounded-xl p-6">
                <div class="flex items-start justify-between mb-5">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-5 flex-1">
                        <div>
                            <p class="text-xs text-ink-muted">Company</p>
                            <p class="text-sm font-medium text-ink mt-0.5">{{ $order->company->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-muted">Order Date</p>
                            <p class="text-sm font-medium text-ink mt-0.5">{{ $order->order_date->format('d M Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-muted">Status</p>
                            <span @class([
                                'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-0.5',
                                'bg-status-pending/10 text-status-pending' => $order->status === 'open',
                                'bg-status-paid/10 text-status-paid' => $order->status === 'completed',
                                'bg-status-overdue/10 text-status-overdue' => $order->status === 'cancelled',
                            ])>
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                @if ($order->description)
                    <p class="text-sm text-ink-muted pt-4 border-t border-border">{{ $order->description }}</p>
                @endif
            </div>

            {{-- Value breakdown --}}
            <div class="bg-surface border border-border rounded-xl p-6">
                <p class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-4">Order Value</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-ink-muted">Total Order Value</p>
                        <p class="text-lg font-semibold text-ink mt-0.5">&#8377;{{ number_format($order->total_value, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted">Total Billed</p>
                        <p class="text-lg font-semibold text-signal mt-0.5">&#8377;{{ number_format($order->amount_billed, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted">Received</p>
                        <p class="text-lg font-semibold text-status-paid mt-0.5">&#8377;{{ number_format($order->amount_received, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted">Remaining to Bill</p>
                        <p class="text-lg font-semibold {{ $order->amount_remaining > 0 ? 'text-status-pending' : 'text-ink' }} mt-0.5">
                            &#8377;{{ number_format($order->amount_remaining, 2) }}
                        </p>
                    </div>
                </div>

                @php
                    $billedPct = $order->total_value > 0 ? min(100, ($order->amount_billed / $order->total_value) * 100) : 0;
                @endphp
                <div class="mt-5 h-2 bg-canvas rounded-full overflow-hidden">
                    <div class="h-full bg-signal rounded-full" style="width: {{ $billedPct }}%"></div>
                </div>
                <p class="mt-1.5 text-xs text-ink-muted">{{ number_format($billedPct, 0) }}% billed</p>
            </div>

            {{-- Linked invoices --}}
            @php $draftInvoices = $invoices->where('status', 'draft'); @endphp
            <div class="bg-surface border border-border rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-wide">Invoices on this Order</p>
                    @if ($order->status === 'open' && $invoices->isEmpty())
                        <a href="{{ route('invoices.create', ['order_id' => $order->id]) }}"
                           class="text-sm font-medium text-signal hover:text-signal-hover">
                            + Bill Against This Order
                        </a>
                    @elseif ($order->status === 'open' && $order->amount_remaining > 0)
                        <a href="{{ route('orders.generate-invoice.create', $order) }}"
                           class="text-sm font-medium text-signal hover:text-signal-hover">
                            + Generate Next Invoice
                        </a>
                    @endif
                </div>

                @if ($draftInvoices->isNotEmpty())
                    <div class="px-6 py-3 bg-status-pending/10 border-b border-status-pending/20 text-status-pending text-sm">
                        This order has {{ $draftInvoices->count() }} unfinalized draft invoice{{ $draftInvoices->count() === 1 ? '' : 's' }}
                        ({{ $draftInvoices->pluck('invoice_number')->implode(', ') }}) — a draft doesn't count towards "Total Billed" above.
                        Check and finalize or delete it before generating another invoice, to avoid over-committing this order.
                    </div>
                @endif

                <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody>
                        @forelse ($invoices as $invoice)
                            @php
                                $statusStyles = [
                                    'draft' => 'bg-status-pending/10 text-status-pending',
                                    'finalized' => 'bg-signal/10 text-signal',
                                    'sent' => 'bg-status-paid/10 text-status-paid',
                                    'archived' => 'bg-border text-ink-muted',
                                ];
                            @endphp
                            <tr class="{{ !$loop->last ? 'border-b border-border' : '' }} hover:bg-canvas/60 transition cursor-pointer"
                                onclick="window.location='{{ route('invoices.show', $invoice) }}'">
                                <td class="px-6 py-4 font-mono text-ink">{{ $invoice->invoice_number }}</td>
                                <td class="px-6 py-4 text-ink-muted">{{ $invoice->invoice_date->format('d M Y') }}</td>
                                <td class="px-6 py-4 text-right font-mono text-ink">&#8377;{{ number_format($invoice->total_amount, 2) }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusStyles[$invoice->status] ?? 'bg-border text-ink-muted' }}">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-4">
                                        @if ($invoice->status === 'draft')
                                            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}"
                                                  onclick="event.stopPropagation()"
                                                  onsubmit="return confirm('Delete this draft invoice? This cannot be undone.');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-status-overdue hover:text-status-overdue/80">Delete</button>
                                            </form>
                                        @endif
                                        <span class="text-signal">View →</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-6 py-16 text-center">
                                    <p class="text-sm text-ink-muted">No invoices billed against this order yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
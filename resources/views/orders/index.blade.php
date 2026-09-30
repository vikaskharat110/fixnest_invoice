<x-app-layout>
    <x-slot name="header">
        <div>
            @if ($activeCompany)
                <div class="flex items-center gap-2">
                    <a href="{{ route('orders.index') }}" class="text-ink-muted hover:text-ink">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </a>
                    <h2 class="font-semibold text-xl text-ink leading-tight">{{ $activeCompany->name }}</h2>
                </div>
                <p class="text-xs text-ink-muted mt-0.5">{{ $orders->total() }} order{{ $orders->total() === 1 ? '' : 's' }}</p>
            @else
                <h2 class="font-semibold text-xl text-ink leading-tight">Sales Orders</h2>
                <p class="text-xs text-ink-muted mt-0.5">{{ $groupedCompanies->total() }} compan{{ $groupedCompanies->total() === 1 ? 'y' : 'ies' }} with orders</p>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 px-4 py-3 bg-status-paid/10 border border-status-paid/20 text-status-paid text-sm rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            <form method="GET" action="{{ route('orders.index') }}"
                  class="bg-surface border border-border rounded-xl p-4 mb-4 flex flex-wrap items-end gap-3">
                @if ($activeCompany)
                    <input type="hidden" name="company_id" value="{{ $activeCompany->id }}">
                @endif

                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-ink-muted mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="{{ $activeCompany ? 'Order number' : 'Company name or order number' }}"
                           class="w-full rounded-lg border-border text-sm focus:border-signal focus:ring-signal">
                </div>

                <div class="w-44">
                    <label class="block text-xs font-medium text-ink-muted mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border-border text-sm focus:border-signal focus:ring-signal">
                        <option value="">All statuses</option>
                        <option value="open" @selected(request('status') === 'open')>Open</option>
                        <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                        <option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-ink text-white text-sm font-medium rounded-lg hover:bg-ink/90 transition">
                        Filter
                    </button>
                    @if (request()->anyFilled(['search', 'status']))
                        <a href="{{ $activeCompany ? route('orders.index', ['company_id' => $activeCompany->id]) : route('orders.index') }}"
                           class="px-4 py-2 text-ink-muted text-sm font-medium rounded-lg hover:bg-canvas transition">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            @if ($activeCompany)
                {{-- Flat order list for this company --}}
                <div class="bg-surface border border-border rounded-xl overflow-hidden overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-canvas/50 text-xs font-medium text-ink-muted uppercase tracking-wide">
                                <th class="text-left px-6 py-3">Order #</th>
                                <th class="text-left px-6 py-3">Date</th>
                                <th class="text-right px-6 py-3">Order Value</th>
                                <th class="text-right px-6 py-3">Billed</th>
                                <th class="text-right px-6 py-3">Remaining</th>
                                <th class="text-left px-6 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr class="{{ !$loop->last ? 'border-b border-border' : '' }} hover:bg-canvas/60 transition cursor-pointer"
                                    onclick="window.location='{{ route('orders.show', $order) }}'">
                                    <td class="px-6 py-4 font-mono text-ink">{{ $order->order_number }}</td>
                                    <td class="px-6 py-4 text-ink-muted">{{ $order->order_date->format('d M Y') }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-ink">&#8377;{{ number_format($order->total_value, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-ink-muted">&#8377;{{ number_format($order->amount_billed, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-mono {{ $order->amount_remaining > 0 ? 'text-status-pending' : 'text-status-paid' }}">
                                        &#8377;{{ number_format($order->amount_remaining, 2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span @class([
                                            'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                            'bg-status-pending/10 text-status-pending' => $order->status === 'open',
                                            'bg-status-paid/10 text-status-paid' => $order->status === 'completed',
                                            'bg-status-overdue/10 text-status-overdue' => $order->status === 'cancelled',
                                        ])>
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center text-ink-muted">No orders match this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($orders->hasPages())
                    <div class="mt-4">{{ $orders->links() }}</div>
                @endif
            @else
                {{-- Company summary rows --}}
                <div class="bg-surface border border-border rounded-xl overflow-hidden overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border bg-canvas/50 text-xs font-medium text-ink-muted uppercase tracking-wide">
                                <th class="text-left px-6 py-3">Company</th>
                                <th class="text-right px-6 py-3">Orders</th>
                                <th class="text-right px-6 py-3">Order Value</th>
                                <th class="text-right px-6 py-3">Billed</th>
                                <th class="text-right px-6 py-3">Remaining</th>
                                <th class="text-right px-6 py-3">Received</th>
                                <th class="text-right px-6 py-3">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($groupedCompanies as $company)
                                @php
                                    $totalValue = (float) ($company->sales_orders_sum_total_value ?? 0);
                                    $billed = (float) $company->salesOrders->sum('amount_billed');
                                    $received = (float) $company->salesOrders->sum('amount_received');
                                    $remaining = max(0, $totalValue - $billed);
                                    $outstanding = max(0, $billed - $received);
                                    $ordersUrl = route('orders.index', array_filter(['company_id' => $company->id, 'status' => request('status')]));
                                @endphp
                                <tr class="border-b border-border last:border-0 hover:bg-canvas/60 transition cursor-pointer"
                                    onclick="window.location='{{ $ordersUrl }}'">
                                    <td class="px-6 py-4 font-medium text-ink">{{ $company->name }}</td>
                                    <td class="px-6 py-4 text-right text-ink">{{ $company->sales_orders_count }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-ink">&#8377;{{ number_format($totalValue, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-ink-muted">&#8377;{{ number_format($billed, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-mono {{ $remaining > 0 ? 'text-status-pending' : 'text-status-paid' }}">&#8377;{{ number_format($remaining, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-mono text-status-paid">&#8377;{{ number_format($received, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-mono {{ $outstanding > 0 ? 'text-status-overdue' : 'text-ink-muted' }}">&#8377;{{ number_format($outstanding, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <p class="text-sm text-ink-muted">No sales orders yet.</p>
                                        <a href="{{ route('invoices.create') }}" class="inline-block mt-2 text-sm text-signal font-medium">Create an invoice to start one →</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($groupedCompanies->hasPages())
                    <div class="mt-4">{{ $groupedCompanies->links() }}</div>
                @endif
            @endif

        </div>
    </div>
</x-app-layout>

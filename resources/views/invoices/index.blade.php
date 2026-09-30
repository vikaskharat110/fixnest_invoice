<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-ink leading-tight">Invoice History</h2>
                <p class="text-xs text-ink-muted mt-0.5">{{ $invoices->total() }} invoice{{ $invoices->total() === 1 ? '' : 's' }}</p>
            </div>
            <a href="{{ route('invoices.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-signal text-white text-sm font-medium rounded-lg hover:bg-signal-hover transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create Invoice
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 px-4 py-3 bg-status-paid/10 border border-status-paid/20 text-status-paid text-sm rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            <form method="GET" action="{{ route('invoices.history') }}"
                  class="bg-surface border border-border rounded-xl p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-wrap lg:items-end gap-3">

                <div class="sm:col-span-2 lg:flex-1 lg:min-w-[200px]">
                    <label class="block text-xs font-medium text-ink-muted mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Buyer name or invoice number"
                           class="w-full rounded-lg border-border text-sm focus:border-signal focus:ring-signal">
                </div>

                <div class="lg:w-44">
                    <label class="block text-xs font-medium text-ink-muted mb-1">Status</label>
                    <select name="status" class="w-full rounded-lg border-border text-sm focus:border-signal focus:ring-signal">
                        <option value="">All statuses</option>
                        @foreach (['draft' => 'Draft', 'finalized' => 'Finalized', 'sent' => 'Sent', 'archived' => 'Archived'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:w-56">
                    <label class="block text-xs font-medium text-ink-muted mb-1">Company</label>
                    <select name="company_id" class="w-full rounded-lg border-border text-sm focus:border-signal focus:ring-signal">
                        <option value="">All companies</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2 lg:col-span-1 flex gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-ink text-white text-sm font-medium rounded-lg hover:bg-ink/90 transition">
                        Filter
                    </button>
                    @if (request()->anyFilled(['search', 'status', 'company_id']))
                        <a href="{{ route('invoices.history') }}"
                           class="px-4 py-2 text-ink-muted text-sm font-medium rounded-lg hover:bg-canvas transition">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            <div class="bg-surface border border-border rounded-xl overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-canvas/50 text-xs font-medium text-ink-muted uppercase tracking-wide">
                            <th class="px-6 py-3 text-left whitespace-nowrap">Invoice #</th>
                            <th class="px-6 py-3 text-left whitespace-nowrap">Buyer</th>
                            <th class="px-6 py-3 text-left whitespace-nowrap">Company</th>
                            <th class="px-6 py-3 text-left whitespace-nowrap">Date</th>
                            <th class="px-6 py-3 text-right whitespace-nowrap">Total</th>
                            <th class="px-6 py-3 text-left whitespace-nowrap">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
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
                            <tr class="border-b border-border last:border-0 hover:bg-canvas/40 cursor-pointer"
                                onclick="window.location='{{ route('invoices.show', $invoice) }}'">
                                <td class="px-6 py-3.5 whitespace-nowrap font-mono text-ink">{{ $invoice->invoice_number }}@if ($invoice->number_source === 'random') <span class="ml-1.5 px-1.5 py-0.5 text-[10px] font-sans font-medium uppercase tracking-wide rounded bg-signal/10 text-signal">Random</span>@endif</td>
                                <td class="px-6 py-3.5 whitespace-nowrap font-medium text-ink">{{ $invoice->buyer_name }}</td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-ink-muted">{{ $invoice->company->name }}</td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-ink-muted">{{ $invoice->invoice_date->format('d M Y') }}</td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-right font-mono text-ink">&#8377;{{ \App\Support\IndianNumber::format($invoice->total_amount) }}</td>
                                <td class="px-6 py-3.5 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $statusStyles[$invoice->status] ?? 'bg-border text-ink-muted' }}">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 whitespace-nowrap text-right text-signal text-sm font-medium">View</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <p class="text-ink-muted">No invoices found.</p>
                                    <a href="{{ route('invoices.create') }}" class="inline-block mt-2 text-sm text-signal font-medium">Create your first invoice →</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($invoices->hasPages())
                <div class="mt-4">{{ $invoices->links() }}</div>
            @endif

        </div>
    </div>
</x-app-layout>

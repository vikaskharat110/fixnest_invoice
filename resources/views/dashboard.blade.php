<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Stat cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-surface border border-border rounded-2xl p-5 shadow-sm shadow-black/[0.03]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-ink/5 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-ink" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-ink-muted">Total Invoices</p>
                            <p class="text-2xl font-semibold text-ink leading-tight">{{ $totalInvoices }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-surface border border-border rounded-2xl p-5 shadow-sm shadow-black/[0.03]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-status-paid/10 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-status-paid" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-ink-muted">Paid</p>
                            <p class="text-2xl font-semibold text-status-paid leading-tight">{{ $paidCount }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-surface border border-border rounded-2xl p-5 shadow-sm shadow-black/[0.03]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-status-pending/10 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-status-pending" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-ink-muted">Pending</p>
                            <p class="text-2xl font-semibold text-status-pending leading-tight">{{ $pendingCount }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-surface border border-border rounded-2xl p-5 shadow-sm shadow-black/[0.03]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-ink-muted/10 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-ink-muted">Drafts</p>
                            <p class="text-2xl font-semibold text-ink-muted leading-tight">{{ $draftCount }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Revenue hero card --}}
            <div class="bg-gradient-to-br from-signal to-signal-hover rounded-2xl p-6 text-white shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-white/70 mb-4">Revenue Summary</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                    <div>
                        <p class="text-xs text-white/70">Total Invoiced</p>
                        <p class="text-2xl font-semibold mt-1">&#8377;{{ \App\Support\IndianNumber::format($totalRevenue) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-white/70">GST</p>
                        <p class="text-2xl font-semibold mt-1">&#8377;{{ \App\Support\IndianNumber::format($totalGst) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-white/70">Received</p>
                        <p class="text-2xl font-semibold mt-1">&#8377;{{ \App\Support\IndianNumber::format($totalReceived) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-white/70">Outstanding</p>
                        <p class="text-2xl font-semibold mt-1">&#8377;{{ \App\Support\IndianNumber::format($totalOutstanding) }}</p>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div>
                <p class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-3">Quick Actions</p>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <a href="{{ route('invoices.create') }}" class="flex flex-col items-center gap-2 bg-surface border border-border rounded-xl p-4 hover:border-signal/40 hover:bg-signal/5 transition text-center">
                        <div class="w-9 h-9 rounded-lg bg-signal/10 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-signal" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-ink">Create Invoice</span>
                    </a>
                    <a href="{{ route('orders.index') }}" class="flex flex-col items-center gap-2 bg-surface border border-border rounded-xl p-4 hover:border-signal/40 hover:bg-signal/5 transition text-center">
                        <div class="w-9 h-9 rounded-lg bg-signal/10 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-signal" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-ink">Sales Orders</span>
                    </a>
                    <a href="{{ route('reports.index') }}" class="flex flex-col items-center gap-2 bg-surface border border-border rounded-xl p-4 hover:border-signal/40 hover:bg-signal/5 transition text-center">
                        <div class="w-9 h-9 rounded-lg bg-signal/10 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-signal" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-ink">Reports</span>
                    </a>
                    <a href="{{ route('companies.index') }}" class="flex flex-col items-center gap-2 bg-surface border border-border rounded-xl p-4 hover:border-signal/40 hover:bg-signal/5 transition text-center">
                        <div class="w-9 h-9 rounded-lg bg-signal/10 flex items-center justify-center">
                            <svg class="w-4.5 h-4.5 text-signal" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                            </svg>
                        </div>
                        <span class="text-xs font-medium text-ink">Companies</span>
                    </a>
                </div>
            </div>

            {{-- Recent invoices + breakdown --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 bg-surface border border-border rounded-2xl overflow-hidden shadow-sm shadow-black/[0.03]">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                        <p class="text-sm font-semibold text-ink">Recent Invoices</p>
                        <a href="{{ route('invoices.history') }}" class="text-sm text-signal font-medium hover:text-signal-hover">View all</a>
                    </div>
                    <div class="divide-y divide-border">
                        @forelse ($recentInvoices as $invoice)
                            @php
                                $statusStyles = [
                                    'draft' => 'bg-status-pending/10 text-status-pending',
                                    'finalized' => 'bg-signal/10 text-signal',
                                    'sent' => 'bg-status-paid/10 text-status-paid',
                                    'archived' => 'bg-border text-ink-muted',
                                ];
                                $statusDotColor = [
                                    'draft' => 'bg-status-pending',
                                    'finalized' => 'bg-signal',
                                    'sent' => 'bg-status-paid',
                                    'archived' => 'bg-ink-muted',
                                ];
                            @endphp
                            <a href="{{ route('invoices.show', $invoice) }}" class="flex items-center gap-3 px-6 py-3.5 hover:bg-canvas/50 transition">
                                <div class="w-9 h-9 rounded-full {{ $statusStyles[$invoice->status] ?? 'bg-border text-ink-muted' }} flex items-center justify-center shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusDotColor[$invoice->status] ?? 'bg-ink-muted' }}"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-ink truncate">{{ $invoice->buyer_name }}</p>
                                    <p class="text-xs text-ink-muted font-mono">{{ $invoice->invoice_number }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-mono text-ink">&#8377;{{ \App\Support\IndianNumber::format($invoice->total_amount) }}</p>
                                    <p class="text-xs {{ $invoice->status === 'draft' ? 'text-status-pending' : 'text-ink-muted' }}">{{ ucfirst($invoice->status) }}</p>
                                </div>
                            </a>
                        @empty
                            <div class="px-6 py-12 text-center text-ink-muted text-sm">
                                No invoices yet.
                                <a href="{{ route('invoices.create') }}" class="text-signal font-medium">Create your first invoice</a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-surface border border-border rounded-2xl p-6 shadow-sm shadow-black/[0.03]">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-4">By Type</p>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-ink-muted">GST Invoices</span>
                            <span class="font-medium text-ink">{{ $gstCount }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-ink-muted">Non-GST Invoices</span>
                            <span class="font-medium text-ink">{{ $nonGstCount }}</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-border space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-ink-muted">Finalized</span>
                            <span class="font-medium text-ink">{{ $finalizedCount }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-ink-muted">Sent</span>
                            <span class="font-medium text-ink">{{ $sentCount }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-ink-muted">Archived</span>
                            <span class="font-medium text-ink">{{ $archivedCount }}</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>

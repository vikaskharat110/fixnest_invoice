<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-ink leading-tight">Companies</h2>
                <p class="text-xs text-ink-muted mt-0.5">{{ $companies->total() }} total</p>
            </div>
            <a href="{{ route('companies.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-signal text-white text-sm font-medium rounded-lg hover:bg-signal-hover transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Company
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

            <div class="bg-surface border border-border rounded-xl overflow-hidden">
                @forelse ($companies as $company)
                    <div class="flex flex-col md:flex-row md:items-center gap-3 md:gap-4 px-6 py-4 {{ !$loop->last ? 'border-b border-border' : '' }} hover:bg-canvas/60 transition">

                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-10 h-10 rounded-lg bg-canvas border border-border flex items-center justify-center shrink-0 overflow-hidden">
                                <span class="text-sm font-semibold text-ink-muted">{{ strtoupper(substr($company->name, 0, 1)) }}</span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-ink truncate">{{ $company->name }}</p>
                                <p class="text-xs text-ink-muted font-mono mt-0.5">{{ $company->gst_number ?? 'No GSTIN' }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 md:gap-4 md:ml-auto">
                            <div class="md:w-24 md:shrink-0 text-sm text-ink-muted font-mono">
                                {{ $company->invoices_count }} {{ Str::plural('invoice', $company->invoices_count) }}
                            </div>

                            <div class="md:w-28 md:shrink-0">
                                <a href="{{ route('orders.index', ['company_id' => $company->id]) }}"
                                   class="text-sm text-signal hover:text-signal-hover font-medium">
                                    {{ $company->sales_orders_count }} {{ Str::plural('order', $company->sales_orders_count) }}
                                </a>
                            </div>

                            <div class="md:w-20 md:shrink-0">
                                @if ($company->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-status-paid/10 text-status-paid">
                                        <span class="w-1.5 h-1.5 rounded-full bg-status-paid"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-status-overdue/10 text-status-overdue">
                                        <span class="w-1.5 h-1.5 rounded-full bg-status-overdue"></span> Inactive
                                    </span>
                                @endif
                            </div>

                            <div class="shrink-0">
                                <a href="{{ route('companies.edit', $company) }}"
                                   class="text-signal hover:text-signal-hover text-sm font-medium">Edit</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-16 text-center">
                        <p class="text-sm text-ink-muted">No companies yet.</p>
                        <a href="{{ route('companies.create') }}" class="inline-block mt-2 text-sm text-signal font-medium">Add your first company →</a>
                    </div>
                @endforelse
            </div>

            @if ($companies->hasPages())
                <div class="mt-4">{{ $companies->links() }}</div>
            @endif

        </div>
    </div>
</x-app-layout>
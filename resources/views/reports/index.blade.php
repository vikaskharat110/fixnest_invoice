<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">Reports</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Tabs --}}
            <div class="flex gap-1 mb-4 border-b border-border">
                @foreach ([
                    'gst' => 'GST Summary',
                    'revenue' => 'Revenue',
                    'outstanding' => 'Outstanding',
                    'trend' => 'Monthly Trend',
                ] as $key => $label)
                    <a href="{{ route('reports.index', array_merge(request()->except('type'), ['type' => $key])) }}"
                       class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition {{ $type === $key ? 'border-signal text-signal' : 'border-transparent text-ink-muted hover:text-ink' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('reports.index') }}"
                  class="bg-surface border border-border rounded-xl p-4 mb-4 flex flex-wrap items-end gap-3">
                <input type="hidden" name="type" value="{{ $type }}">

                @if (in_array($type, ['gst', 'revenue', 'trend']))
                    <div>
                        <label class="block text-xs font-medium text-ink-muted mb-1">From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                               class="rounded-lg border-border text-sm focus:border-signal focus:ring-signal">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-ink-muted mb-1">To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                               class="rounded-lg border-border text-sm focus:border-signal focus:ring-signal">
                    </div>
                @endif

                <div class="w-56">
                    <label class="block text-xs font-medium text-ink-muted mb-1">Company</label>
                    <select name="company_id" class="w-full rounded-lg border-border text-sm focus:border-signal focus:ring-signal">
                        <option value="">All companies</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}" @selected((string) request('company_id') === (string) $company->id)>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($type === 'gst')
                    <label class="flex items-center gap-2 text-sm text-ink-muted pb-2.5">
                        <input type="checkbox" name="include_drafts" value="1" @checked(request('include_drafts'))
                               class="rounded border-border text-signal focus:ring-signal">
                        Include drafts
                    </label>
                @endif

                @if ($type === 'revenue')
                    <div class="w-44">
                        <label class="block text-xs font-medium text-ink-muted mb-1">Status</label>
                        <select name="status" class="w-full rounded-lg border-border text-sm focus:border-signal focus:ring-signal">
                            <option value="">All statuses</option>
                            @foreach (['draft' => 'Draft', 'finalized' => 'Finalized', 'sent' => 'Sent', 'archived' => 'Archived'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-ink text-white text-sm font-medium rounded-lg hover:bg-ink/90 transition">
                        Apply
                    </button>
                    @if (request()->except(['type', 'page']) !== [])
                        <a href="{{ route('reports.index', ['type' => $type]) }}"
                           class="px-4 py-2 text-ink-muted text-sm font-medium rounded-lg hover:bg-canvas transition">
                            Clear
                        </a>
                    @endif
                    <a href="{{ route('reports.export', request()->query()) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-signal text-white text-sm font-medium rounded-lg hover:bg-signal-hover transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        Export CSV
                    </a>
                </div>
            </form>

            @if ($type === 'outstanding')
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                    <div class="bg-surface border border-border rounded-xl p-4">
                        <p class="text-xs text-ink-muted">Total Outstanding</p>
                        <p class="text-xl font-semibold text-ink mt-1">&#8377;{{ \App\Support\IndianNumber::format($report['totals']['total_outstanding']) }}</p>
                    </div>
                    <div class="bg-surface border border-border rounded-xl p-4">
                        <p class="text-xs text-ink-muted">Invoices</p>
                        <p class="text-xl font-semibold text-ink mt-1">{{ $report['totals']['count'] }}</p>
                    </div>
                    <div class="bg-surface border border-border rounded-xl p-4">
                        <p class="text-xs text-ink-muted">Overdue</p>
                        <p class="text-xl font-semibold {{ $report['totals']['overdue_count'] > 0 ? 'text-status-overdue' : 'text-ink' }} mt-1">{{ $report['totals']['overdue_count'] }}</p>
                    </div>
                </div>
            @endif

            @if ($type === 'trend' && !empty($report['chartRows']))
                <div class="bg-surface border border-border rounded-xl p-6 mb-4">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-4">Revenue by Month</p>
                    <div class="space-y-2.5">
                        @foreach ($report['chartRows'] as $row)
                            <div class="flex items-center gap-3">
                                <div class="w-16 shrink-0 text-xs text-ink-muted">{{ $row['period'] }}</div>
                                <div class="flex-1 bg-canvas rounded h-6 overflow-hidden">
                                    <div class="bg-signal h-full rounded" style="width: {{ $row['pct'] }}%"></div>
                                </div>
                                <div class="w-28 shrink-0 text-xs font-mono text-ink text-right">&#8377;{{ \App\Support\IndianNumber::format($row['total']) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Table --}}
            <div class="bg-surface border border-border rounded-xl overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-border bg-canvas/50 text-xs font-medium text-ink-muted uppercase tracking-wide">
                            @foreach ($report['headers'] as $header)
                                <th class="px-4 py-3 text-left whitespace-nowrap">{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $row)
                            <tr class="border-b border-border last:border-0 hover:bg-canvas/40">
                                @foreach ($row as $i => $cell)
                                    <td class="px-4 py-2.5 whitespace-nowrap {{ $i === 0 ? 'text-ink font-medium' : 'text-ink-muted font-mono' }}">
                                        {{ $cell }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($report['headers']) }}" class="px-4 py-12 text-center text-ink-muted">
                                    No data for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if (!empty($report['totalsRow']) && count($report['rows']) > 0)
                        <tfoot>
                            <tr class="border-t-2 border-border font-semibold">
                                @foreach ($report['totalsRow'] as $i => $cell)
                                    <td class="px-4 py-3 whitespace-nowrap {{ $i === 0 ? 'text-ink' : 'text-ink font-mono' }}">
                                        {{ $cell }}
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            @if (!empty($report['paginator']) && $report['paginator']->hasPages())
                <div class="mt-4">{{ $report['paginator']->links() }}</div>
            @endif

        </div>
    </div>
</x-app-layout>

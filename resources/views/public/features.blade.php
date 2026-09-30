@extends('public.layout')
@section('title', 'Features')
@section('content')

    <section class="fx-container py-16">
        <div class="mb-12 max-w-2xl">
            <span class="font-mono text-[11px] tracking-wider uppercase text-signal mb-4 block">Feature ledger</span>
            <h1 class="font-display font-semibold text-3xl sm:text-4xl text-ink mb-3 tracking-tight">Everything on the invoice, nothing extra</h1>
            <p class="text-ink-muted max-w-lg leading-relaxed">A focused toolset for GST-compliant, multi-company invoicing — itemized below.</p>
        </div>

        @php
            $features = [
                ['tag' => 'Tax', 'title' => 'GST tax invoices', 'desc' => 'Generate compliant tax invoices with automatic CGST, SGST or IGST breakdown based on buyer state.'],
                ['tag' => 'Ops', 'title' => 'Multi-company support', 'desc' => 'Manage separate companies, each with their own GSTIN, bank details, and invoice numbering, from one account.'],
                ['tag' => 'Ops', 'title' => 'Sales order tracking', 'desc' => 'Link invoices to sales orders and always know how much of an order has been billed and how much remains.'],
                ['tag' => 'Money', 'title' => 'Payment recording', 'desc' => 'Log payments against invoices and see received and outstanding amounts update automatically.'],
                ['tag' => 'Docs', 'title' => 'PDF & print-ready invoices', 'desc' => 'Every invoice renders as a clean, professional PDF ready to send or print.'],
                ['tag' => 'Docs', 'title' => 'Company directory', 'desc' => 'Keep company details, GSTINs, and addresses organized and reusable across invoices.'],
                ['tag' => 'Tax', 'title' => 'Financial-year numbering', 'desc' => 'Invoice numbers automatically reset by financial year, calendar year, or never — your choice.'],
                ['tag' => 'Money', 'title' => 'Reports & dashboard', 'desc' => 'See total invoiced, received, and outstanding amounts at a glance, with a breakdown by status.'],
            ];
        @endphp

        <div class="bg-surface border border-border rounded-lg overflow-hidden">
            <div class="hidden sm:flex px-6 py-3 bg-canvas border-b border-border font-mono text-[11px] tracking-wider uppercase text-ink-muted">
                <span class="w-10">No.</span>
                <span class="flex-1">Description</span>
                <span class="w-20 text-right">Category</span>
            </div>
            @foreach ($features as $i => $feature)
                <div class="flex flex-col sm:flex-row sm:items-start gap-1.5 sm:gap-0 px-6 py-5 {{ !$loop->last ? 'border-b border-border' : '' }}">
                    <span class="font-mono text-sm text-ink-muted sm:w-10 shrink-0">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <div class="flex-1">
                        <h3 class="font-display font-semibold text-ink text-base mb-1">{{ $feature['title'] }}</h3>
                        <p class="text-sm text-ink-muted leading-relaxed max-w-2xl">{{ $feature['desc'] }}</p>
                    </div>
                    <span class="sm:w-20 text-left sm:text-right shrink-0 font-mono text-[11px] text-signal uppercase tracking-wide">{{ $feature['tag'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

@endsection

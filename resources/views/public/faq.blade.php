@extends('public.layout')
@section('title', 'FAQ')
@section('content')

    <section class="fx-container py-16" x-data="{ open: null }">
        <div class="mb-12 max-w-2xl">
            <span class="font-mono text-[11px] tracking-wider uppercase text-signal mb-4 block">FAQ</span>
            <h1 class="font-display font-semibold text-3xl sm:text-4xl text-ink mb-3 tracking-tight">Frequently asked questions</h1>
            <p class="text-ink-muted">Answers to common questions about Fixnest.</p>
        </div>

        @php
            $faqs = [
                ['q' => 'Who can access Fixnest?', 'a' => 'Fixnest is for authorized administrators only. Access to the dashboard requires a login; there is no public sign-up.'],
                ['q' => 'Does Fixnest support GST invoicing?', 'a' => 'Yes. Fixnest automatically applies CGST/SGST or IGST based on your customer\'s state, and also supports non-GST invoices.'],
                ['q' => 'Can I manage more than one company?', 'a' => 'Yes. Fixnest supports multiple companies under one account, each with its own GSTIN, bank details, and invoice numbering.'],
                ['q' => 'How does invoice numbering work?', 'a' => 'You can choose whether invoice numbers reset every financial year, every calendar year, or never reset at all.'],
                ['q' => 'Can I track partial payments on an invoice?', 'a' => 'Yes. You can record one or more payments against an invoice, and Fixnest keeps received and outstanding amounts up to date automatically.'],
                ['q' => 'Can I download invoices as PDF?', 'a' => 'Yes, every invoice can be viewed and downloaded as a print-ready PDF.'],
                ['q' => 'How do I get access to the dashboard?', 'a' => 'Reach out through our Contact page and an administrator will help set up your account.'],
            ];
        @endphp

        <div class="border-t border-border">
            @foreach ($faqs as $i => $faq)
                <div class="border-b border-border">
                    <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}"
                            class="w-full flex items-center justify-between gap-4 py-5 text-left group">
                        <span class="flex items-baseline gap-4">
                            <span class="font-mono text-xs text-ink-muted">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="font-display font-semibold text-ink text-base group-hover:text-signal transition">{{ $faq['q'] }}</span>
                        </span>
                        <svg class="w-4 h-4 text-ink-muted shrink-0 transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open === {{ $i }}" x-cloak class="pl-9 pb-5 -mt-1 text-sm text-ink-muted leading-relaxed max-w-3xl">
                        {{ $faq['a'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </section>

@endsection


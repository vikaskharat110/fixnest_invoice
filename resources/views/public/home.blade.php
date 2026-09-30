@extends('public.layout')
@section('title', 'Home')
@section('content')

    {{-- Hero --}}
    <section class="fx-container pt-16 pb-10">
        <div class="grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-20 items-center">
            <div>
                <span class="font-mono text-[11px] tracking-wider uppercase text-signal mb-5 block">Invoice №&nbsp;FX/25-26/00042</span>
                <h1 class="font-display font-semibold text-4xl sm:text-6xl text-ink leading-[1.08] mb-6 tracking-tight">
                    Every tax invoice,<br>every company,<br><span class="italic text-signal">one running total.</span>
                </h1>
                <p class="text-ink-muted text-lg leading-relaxed mb-9 max-w-lg">
                    Fixnest keeps companies, customers, sales orders and payments in sync — so your team always
                    knows what's billed, what's received, and what CGST, SGST or IGST actually applies.
                </p>
                <div class="flex items-center gap-3">
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center px-6 py-3 bg-ink text-white text-sm font-medium rounded-md hover:bg-signal transition">
                        Get started
                    </a>
                    <a href="{{ route('public.features') }}"
                       class="inline-flex items-center px-6 py-3 text-ink text-sm font-medium border-b border-ink/30 hover:border-ink transition">
                        See features →
                    </a>
                </div>
            </div>

            {{-- Signature element: a mock tax invoice --}}
            <div class="relative mx-auto w-full max-w-md">
                <div class="absolute -inset-3 border border-border rounded-lg rotate-2 hidden sm:block"></div>
                <div class="relative bg-surface border border-border rounded-lg shadow-xl shadow-ink/5 p-7 -rotate-1">
                    <div class="flex items-start justify-between mb-4 pb-4 border-b border-dashed border-border">
                        <div>
                            <p class="font-display font-semibold text-ink text-sm">TAX INVOICE</p>
                            <p class="font-mono text-[11px] text-ink-muted mt-0.5">FX/25-26/00042</p>
                        </div>
                        <div class="text-right">
                            <p class="font-mono text-[11px] text-ink-muted">GSTIN</p>
                            <p class="font-mono text-[11px] text-ink">27AACFX1234H1Z5</p>
                        </div>
                    </div>

                    <div class="space-y-2.5 mb-4">
                        <div class="flex justify-between text-[13px]">
                            <span class="text-ink-muted">Web platform — annual</span>
                            <span class="font-mono text-ink">₹48,000.00</span>
                        </div>
                        <div class="flex justify-between text-[13px]">
                            <span class="text-ink-muted">Support &amp; hosting</span>
                            <span class="font-mono text-ink">₹6,000.00</span>
                        </div>
                    </div>

                    <div class="space-y-1.5 pt-3 border-t border-border text-[12px]">
                        <div class="flex justify-between text-ink-muted">
                            <span>CGST&nbsp;9%</span>
                            <span class="font-mono">₹4,860.00</span>
                        </div>
                        <div class="flex justify-between text-ink-muted">
                            <span>SGST&nbsp;9%</span>
                            <span class="font-mono">₹4,860.00</span>
                        </div>
                        <div class="flex justify-between text-ink font-semibold text-[14px] pt-1.5 border-t border-border mt-1.5">
                            <span>Total</span>
                            <span class="font-mono">₹63,720.00</span>
                        </div>
                    </div>

                    <div class="absolute -bottom-3 -right-3 w-16 h-16 rounded-full border-2 border-status-paid text-status-paid flex items-center justify-center rotate-[-14deg] bg-surface/90">
                        <span class="font-mono text-[10px] font-bold tracking-wider">PAID</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Feature highlights --}}
    <section class="bg-surface border-y border-border">
        <div class="fx-container pt-10 pb-16">
            <p class="font-mono text-[11px] tracking-wider uppercase text-ink-muted mb-10">What's on the invoice</p>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-x-10 gap-y-14 border-t border-border pt-12">
                <div class="flex items-start gap-5">
                    <span class="font-mono text-sm text-ink-muted pt-0.5">01</span>
                    <div class="flex-1">
                        <h3 class="font-display font-semibold text-ink text-lg mb-1.5">GST invoicing, handled correctly</h3>
                        <p class="text-sm text-ink-muted leading-relaxed">CGST, SGST or IGST is applied automatically based on the customer's state — no manual tax lookups.</p>
                    </div>
                </div>
                <div class="flex items-start gap-5">
                    <span class="font-mono text-sm text-ink-muted pt-0.5">02</span>
                    <div class="flex-1">
                        <h3 class="font-display font-semibold text-ink text-lg mb-1.5">Every company, one dashboard</h3>
                        <p class="text-sm text-ink-muted leading-relaxed">Run invoicing for every entity you manage, each with its own GSTIN, bank details and numbering.</p>
                    </div>
                </div>
                <div class="flex items-start gap-5">
                    <span class="font-mono text-sm text-ink-muted pt-0.5">03</span>
                    <div class="flex-1">
                        <h3 class="font-display font-semibold text-ink text-lg mb-1.5">Nothing outstanding goes unnoticed</h3>
                        <p class="text-sm text-ink-muted leading-relaxed">Track what's billed, received and still outstanding on every sales order, automatically.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA band --}}
    <section class="bg-ink">
        <div class="fx-container-narrow pt-14 pb-6 text-center">
            <p class="font-mono text-[11px] tracking-wider uppercase text-white/40 mb-3">Ready when you are</p>
            <h2 class="font-display font-semibold text-2xl sm:text-3xl text-white mb-7">Close the books with confidence.</h2>
            <a href="{{ route('login') }}"
               class="inline-flex items-center px-6 py-3 bg-canvas text-ink text-sm font-medium rounded-md hover:bg-white transition">
                Login to dashboard
            </a>
        </div>
    </section>

@endsection




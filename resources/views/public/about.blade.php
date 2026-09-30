@extends('public.layout')
@section('title', 'About')
@section('content')

    <section class="fx-container py-16">
        <span class="font-mono text-[11px] tracking-wider uppercase text-signal mb-4 block">About Fixnest</span>
        <h1 class="font-display font-semibold text-3xl sm:text-4xl text-ink mb-10 tracking-tight leading-tight max-w-2xl">Built for businesses that bill across multiple companies</h1>

        <div class="grid grid-cols-1 lg:grid-cols-[1.3fr_1fr] gap-16">
            <div class="space-y-5 text-ink-muted leading-relaxed text-[15px] max-w-xl">
                <p>
                    Fixnest started as a simple idea: invoicing shouldn't require juggling spreadsheets, manually
                    tracking GST rates, or losing sight of which sales orders have already been billed. We built a
                    single tool that keeps companies, customers, sales orders, and tax invoices connected — so nothing
                    falls through the cracks.
                </p>
                <p>
                    Whether you're managing one company or several, Fixnest gives every team the same clear picture:
                    what's been ordered, what's been invoiced, what's been paid, and what's still outstanding.
                </p>
                <p>
                    We designed Fixnest around Indian GST compliance from day one — correct CGST/SGST/IGST handling,
                    financial-year invoice numbering, and clean, print-ready tax invoices — while staying flexible
                    enough for non-GST invoicing too.
                </p>
            </div>

            {{-- Summary block, styled like an invoice totals section --}}
            <div class="lg:sticky lg:top-24 h-fit bg-surface border border-border rounded-lg p-7">
                <p class="font-mono text-[11px] tracking-wider uppercase text-ink-muted mb-4">Summary</p>
                <div class="space-y-3">
                    <div class="flex justify-between items-baseline">
                        <span class="text-sm text-ink-muted">Companies per account</span>
                        <span class="font-display font-semibold text-ink">Unlimited</span>
                    </div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-sm text-ink-muted">Tax compliance</span>
                        <span class="font-display font-semibold text-ink">GST-ready</span>
                    </div>
                    <div class="flex justify-between items-baseline pt-3 border-t border-border">
                        <span class="text-sm font-medium text-ink">Orders, invoices & payments</span>
                        <span class="font-display font-semibold text-ink">Always in sync</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection


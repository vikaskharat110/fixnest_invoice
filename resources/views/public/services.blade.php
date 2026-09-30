@extends('public.layout')
@section('title', 'Services')
@section('content')

    <section class="fx-container py-16">
        <div class="mb-12 max-w-2xl">
            <span class="font-mono text-[11px] tracking-wider uppercase text-signal mb-4 block">Setup order</span>
            <h1 class="font-display font-semibold text-3xl sm:text-4xl text-ink mb-3 tracking-tight">How Fixnest supports your workflow</h1>
            <p class="text-ink-muted max-w-lg leading-relaxed">From setup to day-to-day invoicing, in the order you'll actually use it.</p>
        </div>

        @php
            $services = [
                ['title' => 'Account & company setup', 'desc' => 'We help you get your companies, GSTIN details, and bank information configured correctly from the start.'],
                ['title' => 'GST-compliant invoicing', 'desc' => 'Create tax invoices that automatically apply the correct GST treatment based on your customer\'s state.'],
                ['title' => 'Sales order management', 'desc' => 'Track orders against customers and bill incrementally, with built-in checks so you never over-invoice an order.'],
                ['title' => 'Payment reconciliation', 'desc' => 'Record payments as they arrive and always know which invoices are fully paid and which are still outstanding.'],
                ['title' => 'Ongoing support', 'desc' => 'Our team is on hand to help with questions about invoicing, GST setup, or anything else in Fixnest.'],
            ];
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-[1.4fr_1fr] gap-16">
            <div>
                @foreach ($services as $i => $service)
                    <div class="flex gap-5 {{ !$loop->last ? 'pb-8' : '' }}">
                        <div class="flex flex-col items-center shrink-0">
                            <div class="w-8 h-8 rounded-full bg-ink text-white flex items-center justify-center font-mono text-xs font-semibold shrink-0">
                                {{ $i + 1 }}
                            </div>
                            @if (!$loop->last)
                                <div class="w-px flex-1 bg-border mt-2"></div>
                            @endif
                        </div>
                        <div class="pt-1 {{ !$loop->last ? 'pb-2' : '' }}">
                            <h3 class="font-display font-semibold text-ink text-lg mb-1.5">{{ $service['title'] }}</h3>
                            <p class="text-sm text-ink-muted leading-relaxed max-w-lg">{{ $service['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="lg:sticky lg:top-24 h-fit bg-surface border border-border rounded-lg p-7">
                <p class="font-mono text-[11px] tracking-wider uppercase text-ink-muted mb-4">Need help getting started?</p>
                <p class="text-sm text-ink-muted leading-relaxed mb-6">
                    Whether you're onboarding your first company or migrating several, our team can walk you
                    through setup, GST configuration, and invoice numbering so nothing's left to guesswork.
                </p>
                <a href="{{ route('public.contact') }}"
                   class="inline-flex items-center px-6 py-3 bg-ink text-white text-sm font-medium rounded-md hover:bg-signal transition">
                    Get in touch
                </a>
            </div>
        </div>
    </section>

@endsection


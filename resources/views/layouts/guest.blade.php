<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Fixnest') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:500,600|inter:400,500,600,700|ibm-plex-mono:400,500&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans text-ink antialiased">
        <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2">

            {{-- Left: brand / trust panel --}}
            <div class="hidden lg:flex flex-col bg-ink text-white px-14 py-12 relative overflow-hidden">
                <div class="absolute inset-0 opacity-[0.04] pointer-events-none"
                     style="background-image: repeating-linear-gradient(transparent, transparent 27px, currentColor 27px, currentColor 28px);"></div>

                <a href="{{ route('public.home') }}" class="relative flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-md bg-white text-ink flex items-center justify-center font-display font-semibold text-base">F</span>
                    <span class="font-display font-semibold text-xl tracking-tight">Fixnest</span>
                </a>

                <div class="relative flex-1 flex items-center">
                    <div class="flex items-center gap-8">
                        <div class="max-w-sm"><br/>
                            <h1 class="font-display font-semibold text-4xl leading-tight mb-8">
                                Sign in to keep every invoice, order and payment in sync.
                            </h1>
                            <ul class="space-y-5 text-base text-white/70">
                                <li class="flex items-start gap-3">
                                    <svg class="w-5 h-5 mt-0.5 shrink-0 text-status-paid" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    <span>Every company's GSTIN, numbering and bank details in one place</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="w-5 h-5 mt-0.5 shrink-0 text-status-paid" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    <span>Role-based access — your team sees only what it needs</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <svg class="w-5 h-5 mt-0.5 shrink-0 text-status-paid" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    <span>A full audit trail on every invoice and payment</span>
                                </li>
                            </ul>
                        </div>

                        {{-- Mini invoice mock — now beside the text, not below it --}}
                        <div class="relative w-84 shrink-0 hidden xl:block">
                            <div class="absolute -inset-2.5 border border-white/10 rounded-lg rotate-2"></div>
                            <div class="relative bg-white/[0.06] backdrop-blur-sm border border-white/10 rounded-lg p-5 -rotate-1">
                                <div class="flex items-start justify-between mb-3 pb-3 border-b border-dashed border-white/15">
                                    <div>
                                        <p class="font-display font-semibold text-white text-sm">TAX INVOICE</p>
                                        <p class="font-mono text-[11px] text-white/50 mt-0.5">FX/25-26/00042</p>
                                    </div>
                                    <span class="font-mono text-[10px] px-2 py-0.5 rounded-full border border-status-paid/40 text-status-paid">PAID</span>
                                </div>
                                <div class="space-y-1.5 text-[12px]">
                                    <div class="flex justify-between text-white/60">
                                        <span>CGST&nbsp;9%</span>
                                        <span class="font-mono">₹4,860.00</span>
                                    </div>
                                    <div class="flex justify-between text-white/60">
                                        <span>SGST&nbsp;9%</span>
                                        <span class="font-mono">₹4,860.00</span>
                                    </div>
                                    <div class="flex justify-between text-white font-semibold text-[13px] pt-1.5 border-t border-white/15 mt-1.5">
                                        <span>Total</span>
                                        <span class="font-mono">₹63,720.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="relative font-mono text-xs text-white/30">Access is restricted to authorized administrators.</p>
            </div>

            {{-- Right: form --}}
            <div class="flex items-center justify-center px-6 py-16 bg-canvas">
                <div class="w-full max-w-md">
                    <div class="mb-8 lg:hidden text-center">
                        <span class="font-display font-semibold text-2xl text-ink tracking-tight">Fixnest</span>
                        <p class="mt-1 text-base text-ink-muted">Tax invoice generator</p>
                    </div>

                    <div class="mb-9">
                        <div class="w-11 h-11 rounded-lg bg-signal/10 text-signal flex items-center justify-center mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </div>
                        <h2 class="font-display font-semibold text-3xl text-ink tracking-tight">Sign in to your dashboard</h2>
                        <p class="text-base text-ink-muted mt-2">Enter your admin credentials to continue.</p>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>

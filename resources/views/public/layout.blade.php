<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Fixnest') — Fixnest</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600,600i,700|inter:400,500,600,700|ibm-plex-mono:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas text-ink antialiased">

    {{-- Utility strip --}}
    <div class="bg-ink text-white/70 text-[11px] font-mono tracking-wider uppercase">
        <div class="fx-container py-2 flex items-center justify-between">
            <span>GSTIN-ready · CGST / SGST / IGST</span>
            <span class="hidden sm:inline">Multi-company · Financial-year numbering</span>
        </div>
    </div>

    {{-- Nav --}}
    <header class="bg-surface border-b border-border sticky top-0 z-10" x-data="{ open: false }">
        <div class="fx-container py-4 flex items-center justify-between">
            <a href="{{ route('public.home') }}" class="flex items-center gap-2.5 group">
                <span class="w-8 h-8 rounded-md bg-ink text-white flex items-center justify-center font-display font-semibold text-sm">F</span>
                <span class="font-display font-semibold text-lg text-ink tracking-tight">Fixnest</span>
            </a>
            <nav class="hidden md:flex items-center gap-8 text-[17px] font-medium text-ink-muted">
                <a href="{{ route('public.home') }}" class="hover:text-ink transition">Home</a>
                <a href="{{ route('public.about') }}" class="hover:text-ink transition">About</a>
                <a href="{{ route('public.features') }}" class="hover:text-ink transition">Features</a>
                <a href="{{ route('public.services') }}" class="hover:text-ink transition">Services</a>
                <a href="{{ route('public.contact') }}" class="hover:text-ink transition">Contact</a>
                <a href="{{ route('public.faq') }}" class="hover:text-ink transition">FAQ</a>
            </nav>
            <div class="flex items-center gap-2">
                <a href="{{ route('login') }}"
                   class="hidden sm:inline-flex items-center px-5 py-2.5 bg-ink text-white text-sm font-medium rounded-md hover:bg-signal transition">
                    Login
                </a>
                <button @click="open = !open" class="md:hidden text-ink p-2 -mr-2" aria-label="Toggle menu">
                    <svg x-show="!open" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                    <svg x-show="open" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <nav x-show="open" x-cloak @click="open = false"
             class="md:hidden border-t border-border bg-surface flex flex-col text-sm font-medium text-ink-muted">
            <a href="{{ route('public.home') }}" class="px-4 py-3 border-b border-border hover:text-ink hover:bg-canvas transition">Home</a>
            <a href="{{ route('public.about') }}" class="px-4 py-3 border-b border-border hover:text-ink hover:bg-canvas transition">About</a>
            <a href="{{ route('public.features') }}" class="px-4 py-3 border-b border-border hover:text-ink hover:bg-canvas transition">Features</a>
            <a href="{{ route('public.services') }}" class="px-4 py-3 border-b border-border hover:text-ink hover:bg-canvas transition">Services</a>
            <a href="{{ route('public.contact') }}" class="px-4 py-3 border-b border-border hover:text-ink hover:bg-canvas transition">Contact</a>
            <a href="{{ route('public.faq') }}" class="px-4 py-3 border-b border-border hover:text-ink hover:bg-canvas transition">FAQ</a>
            <a href="{{ route('login') }}" class="px-4 py-3 text-ink font-semibold hover:bg-canvas transition">Login</a>
        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-ink text-white/60">
        <div class="fx-container pt-8 pb-12 grid grid-cols-1 sm:grid-cols-[1.4fr_1fr_1fr] gap-10">
            <div>
                <div class="flex items-center gap-2.5 mb-4">
                    <span class="w-7 h-7 rounded-md bg-canvas text-ink flex items-center justify-center font-display font-semibold text-xs">F</span>
                    <span class="font-display font-semibold text-white text-base">Fixnest</span>
                </div>
                <p class="text-sm leading-relaxed max-w-xs">Tax invoices, sales orders and payments, kept in sync for every company you run.</p>
            </div>
            <div>
                <p class="font-mono text-[11px] tracking-wider uppercase text-white/40 mb-3">Product</p>
                <nav class="flex flex-col gap-2.5 text-sm">
                    <a href="{{ route('public.features') }}" class="hover:text-white transition">Features</a>
                    <a href="{{ route('public.services') }}" class="hover:text-white transition">Services</a>
                    <a href="{{ route('public.faq') }}" class="hover:text-white transition">FAQ</a>
                </nav>
            </div>
            <div>
                <p class="font-mono text-[11px] tracking-wider uppercase text-white/40 mb-3">Company</p>
                <nav class="flex flex-col gap-2.5 text-sm">
                    <a href="{{ route('public.about') }}" class="hover:text-white transition">About</a>
                    <a href="{{ route('public.contact') }}" class="hover:text-white transition">Contact</a>
                    <a href="{{ route('login') }}" class="hover:text-white transition">Login</a>
                </nav>
            </div>
        </div>
        <div class="border-t border-canvas/10">
            <div class="fx-container py-5 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs font-mono text-white/40">
                <span>&copy; {{ date('Y') }} Fixnest</span>
                <span>Built for Indian GST compliance</span>
            </div>
        </div>
    </footer>

</body>
</html>





<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Fixnest') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|ibm-plex-mono:400,500&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased text-ink bg-canvas overflow-hidden" x-data="{ sidebarOpen: false }">
        <div class="h-full flex print:block print:h-auto">
            <div class="print:hidden">
                @include('layouts.navigation')
            </div>

            <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
                 class="fixed inset-0 bg-ink/50 z-30 md:hidden print:hidden"></div>

            <div class="flex-1 flex flex-col min-w-0 h-full overflow-hidden print:block print:h-auto print:overflow-visible">
                <header class="shrink-0 sticky top-0 z-10 print:hidden">
                    @php
                        $hour = now()->format('H');
                        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
                    @endphp

                    <div class="bg-gradient-to-r from-signal to-signal-hover px-6 py-2.5 flex items-center justify-between">
                        <div class="flex items-center gap-3 min-w-0">
                            <button @click="sidebarOpen = true" class="md:hidden shrink-0 text-white/90 hover:text-white">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                                </svg>
                            </button>
                            <div class="min-w-0">
                                <p class="text-[11px] text-white/70 font-medium tracking-wide">{{ $greeting }}</p>
                                <p class="text-white font-semibold text-sm leading-tight truncate">{{ Auth::user()->name }}</p>
                            </div>
                        </div>

                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="flex items-center gap-2.5 group">
                                    <div class="w-9 h-9 rounded-full bg-white/15 border border-white/25 flex items-center justify-center text-sm font-semibold text-white shrink-0">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </div>
                                    <svg class="h-4 w-4 fill-current text-white/80 group-hover:text-white transition" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Profile') }}
                                </x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault(); this.closest('form').submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>

                    <div class="min-h-[2.0rem] bg-surface border-b border-border flex items-center px-6 py-2.5">
                        <div class="flex-1 text-sm font-medium text-ink-muted">
                            @isset($header)
                                {{ $header }}
                            @endisset
                        </div>
                    </div>
                </header>

                <main class="flex-1 overflow-y-auto p-6 print:p-0 print:overflow-visible">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
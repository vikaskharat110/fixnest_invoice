@extends('public.layout')
@section('title', 'Contact')
@section('content')

    <section class="fx-container py-16">
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_1.3fr] gap-16">
            <div>
                <span class="font-mono text-[11px] tracking-wider uppercase text-signal mb-4 block">Contact</span>
                <h1 class="font-display font-semibold text-3xl sm:text-4xl text-ink mb-4 tracking-tight">Get in touch</h1>
                <p class="text-ink-muted leading-relaxed max-w-sm mb-8">Questions about Fixnest, or need help getting set up? Send us a message and our team will get back to you.</p>

                <div class="space-y-5 border-t border-border pt-6">
                    <div>
                        <p class="font-mono text-[11px] tracking-wider uppercase text-ink-muted mb-1">Response time</p>
                        <p class="text-sm text-ink">Usually within one business day</p>
                    </div>
                    <div>
                        <p class="font-mono text-[11px] tracking-wider uppercase text-ink-muted mb-1">Access</p>
                        <p class="text-sm text-ink">Dashboard access is by invitation — contact us to get set up</p>
                    </div>
                </div>
            </div>

            <div>
                @if (session('status'))
                    <div class="mb-6 px-4 py-3 bg-status-paid/10 border border-status-paid/20 text-status-paid text-sm rounded-lg">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('public.contact.submit') }}" class="bg-surface border border-border rounded-lg p-7 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-mono text-[11px] tracking-wider uppercase text-ink-muted mb-1.5">Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full rounded-md border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                            @error('name') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] tracking-wider uppercase text-ink-muted mb-1.5">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   class="w-full rounded-md border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                            @error('email') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-mono text-[11px] tracking-wider uppercase text-ink-muted mb-1.5">Phone (optional)</label>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                   class="w-full rounded-md border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                            @error('phone') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block font-mono text-[11px] tracking-wider uppercase text-ink-muted mb-1.5">Subject (optional)</label>
                            <input type="text" name="subject" value="{{ old('subject') }}"
                                   class="w-full rounded-md border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                            @error('subject') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block font-mono text-[11px] tracking-wider uppercase text-ink-muted mb-1.5">Message</label>
                        <textarea name="message" rows="6" required
                                  class="w-full rounded-md border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">{{ old('message') }}</textarea>
                        @error('message') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-6 py-2.5 bg-ink text-white text-sm font-medium rounded-md hover:bg-signal transition">
                            Send message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

@endsection


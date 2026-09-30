<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('companies.index') }}"
            class="inline-flex items-center gap-1.5 text-xs font-medium text-ink-muted hover:text-ink transition mb-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Companies
            </a>
            <h2 class="font-semibold text-xl text-ink leading-tight">
                {{ $company->exists ? 'Edit Company' : 'Add Company' }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <form method="POST"
                  action="{{ $company->exists ? route('companies.update', $company) : route('companies.store') }}">
                @csrf
                @if ($company->exists) @method('PUT') @endif

                <div class="space-y-6">

                    {{-- Section: Company Details --}}
                    <div class="bg-surface border border-border rounded-xl shadow-sm shadow-black/[0.03]">
                        <div class="px-6 py-4 border-b border-border flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-signal/10 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-signal" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l8-4v18M13 21V11l6 3v7M9 9v.01M9 12v.01M9 15v.01" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-ink">Company Details</h3>
                                <p class="text-xs text-ink-muted mt-0.5">Who this company's invoices are billed to.</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-5">
                            <div>
                                <x-input-label for="name" value="Company Name" />
                                <x-text-input id="name" name="name" type="text" class="mt-1.5"
                                              value="{{ old('name', $company->name) }}" required autofocus />
                                @error('name') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <x-input-label for="address" value="Address" />
                                <textarea id="address" name="address" rows="3"
                                          class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">{{ old('address', $company->address) }}</textarea>
                                @error('address') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <x-input-label for="email" value="Email" />
                                    <x-text-input id="email" name="email" type="email" class="mt-1.5"
                                                  value="{{ old('email', $company->email) }}" />
                                    @error('email') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <x-input-label for="phone" value="Phone" />
                                    <x-text-input id="phone" name="phone" type="text" class="mt-1.5"
                                                  value="{{ old('phone', $company->phone) }}" />
                                    @error('phone') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <label for="is_active" class="flex items-center gap-2.5 cursor-pointer w-fit">
                                <input id="is_active" name="is_active" type="checkbox" value="1"
                                       {{ old('is_active', $company->is_active ?? true) ? 'checked' : '' }}
                                       class="rounded border-border text-signal focus:ring-signal">
                                <span class="text-sm text-ink">Company is active</span>
                            </label>
                        </div>
                    </div>

                    {{-- Section: Tax & Compliance --}}
                    <div class="bg-surface border border-border rounded-xl shadow-sm shadow-black/[0.03]">
                        <div class="px-6 py-4 border-b border-border flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-signal/10 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-signal" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-ink">Tax & Compliance</h3>
                                <p class="text-xs text-ink-muted mt-0.5">Used for GST invoice generation.</p>
                            </div>
                        </div>
                        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <x-input-label for="gst_number" value="GSTIN" />
                                <x-text-input id="gst_number" name="gst_number" type="text" class="mt-1.5 font-mono"
                                              value="{{ old('gst_number', $company->gst_number) }}" />
                                @error('gst_number') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-input-label for="pan_number" value="PAN" />
                                <x-text-input id="pan_number" name="pan_number" type="text" class="mt-1.5 font-mono"
                                              value="{{ old('pan_number', $company->pan_number) }}" />
                                @error('pan_number') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-input-label for="state" value="State" />
                                <x-text-input id="state" name="state" type="text" class="mt-1.5"
                                              value="{{ old('state', $company->state) }}" />
                                @error('state') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Sticky action bar --}}
                <div class="sticky bottom-0 mt-6 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-4 bg-canvas/95 backdrop-blur border-t border-border flex items-center justify-end gap-3">
                    <a href="{{ route('companies.index') }}" class="px-4 py-2.5 text-sm font-medium text-ink-muted hover:text-ink transition">
                        Cancel
                    </a>
                    <x-primary-button>{{ $company->exists ? 'Save Changes' : 'Create Company' }}</x-primary-button>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>

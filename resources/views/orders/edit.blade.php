<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('orders.show', $order) }}"
               class="inline-flex items-center gap-1.5 text-xs font-medium text-ink-muted hover:text-ink transition mb-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ $order->order_number }}
            </a>
            <h2 class="font-semibold text-xl text-ink leading-tight">Edit Sales Order</h2>
        </div>
    </x-slot>

    <div class="py-8">
               <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('orders.update', $order) }}">
                @csrf
                @method('PUT')

                <div class="bg-surface border border-border rounded-xl shadow-sm shadow-black/[0.03] p-6 space-y-5">

                    <div class="pb-5 border-b border-border">
                        <p class="text-xs text-ink-muted">Company</p>
                        <p class="text-sm font-medium text-ink mt-0.5">{{ $order->company->name }}</p>
                    </div>
                    <p class="-mt-3 text-xs text-ink-muted">Company is locked once an order is created.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <x-input-label for="base_value" value="Base Order Value (₹, excl. GST)" />
                            <x-text-input id="base_value" name="base_value" type="number" step="0.01" min="0.01" class="mt-1.5 font-mono"
                                          value="{{ old('base_value', $order->base_value) }}" required
                                          oninput="updateComputedTotal()" />
                            @error('base_value') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="tax_percent" value="Tax %" />
                            <x-text-input id="tax_percent" name="tax_percent" type="number" step="0.01" min="0" max="100" class="mt-1.5 font-mono"
                                          value="{{ old('tax_percent', $order->tax_percent) }}" required
                                          oninput="updateComputedTotal()" />
                            @error('tax_percent') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <p class="text-xs text-ink-muted">
                        Total order value (incl. GST): <span id="computed_total" class="font-mono text-ink">₹{{ number_format($order->total_value, 2) }}</span> — this is the figure invoices are checked against.
                    </p>

                    <div>
                        <x-input-label for="order_date" value="Order Date" />
                        <x-text-input id="order_date" name="order_date" type="date" class="mt-1.5"
                                      value="{{ old('order_date', $order->order_date->format('Y-m-d')) }}" required />
                        @error('order_date') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status" required
                                class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                            <option value="open" @selected(old('status', $order->status) === 'open')>Open</option>
                            <option value="completed" @selected(old('status', $order->status) === 'completed')>Completed</option>
                            <option value="cancelled" @selected(old('status', $order->status) === 'cancelled')>Cancelled</option>
                        </select>
                        @error('status') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-input-label for="description" value="Description (optional)" />
                        <textarea id="description" name="description" rows="3"
                                  class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">{{ old('description', $order->description) }}</textarea>
                        @error('description') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                    </div>

                </div>

                <div class="sticky bottom-0 mt-6 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-4 bg-canvas/95 backdrop-blur border-t border-border flex items-center justify-end gap-3">
                    <a href="{{ route('orders.show', $order) }}" class="px-4 py-2.5 text-sm font-medium text-ink-muted hover:text-ink transition">
                        Cancel
                    </a>
                    <x-primary-button>Save Changes</x-primary-button>
                </div>

            </form>
        </div>
    </div>

    <script>
        function updateComputedTotal() {
            const base = parseFloat(document.getElementById('base_value').value) || 0;
            const tax = parseFloat(document.getElementById('tax_percent').value) || 0;
            const total = base + (base * tax / 100);
            document.getElementById('computed_total').textContent =
                '₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    </script>
</x-app-layout>
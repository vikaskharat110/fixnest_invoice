<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">
            {{ $invoice ? 'Edit Invoice — ' . $invoice->invoice_number : (isset($randomNumberPrefix) ? 'Create Random Invoice' : 'Create Invoice') }}
        </h2>
    </x-slot>

    @if (session('status'))
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <div class="px-4 py-3 bg-status-paid/10 border border-status-paid/20 text-status-paid text-sm rounded-lg">
                {{ session('status') }}
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <div class="px-4 py-3 bg-status-overdue/10 border border-status-overdue/20 text-status-overdue text-sm rounded-lg">
                {{ session('error') }}
            </div>
        </div>
    @endif

    @php
        $initialStep = 1;
        foreach ($errors->keys() as $key) {
            if ($key === 'template') {
                $initialStep = max($initialStep, 5);
            } elseif ($key === 'random_suffix') {
                $initialStep = max($initialStep, 2);
            } elseif (str_starts_with($key, 'items')) {
                $initialStep = max($initialStep, 4);
            } elseif (in_array($key, ['order_mode', 'order_id', 'new_order_base_value', 'new_order_tax_percent', 'new_order_description'])) {
                $initialStep = max($initialStep, 3);
            } elseif (in_array($key, ['invoice_type', 'gst_type', 'invoice_date', 'due_date', 'reference_number', 'delivery_note', 'buyer_order_number'])) {
                $initialStep = max($initialStep, 2);
            }
        }

        // Editing: Company and Sales Order are already locked/valid, so skip
        // straight to Line Items — unless a validation error bounced us back
        // to an earlier step, in which case that still wins.
        if ($invoice && $initialStep === 1) {
            $initialStep = 4;
        }

            $wizardConfig = [
            'companies' => $companies,
            'step' => $initialStep,
            'isEditing' => (bool) $invoice,
            'randomSuffix' => old('random_suffix', ''),
            'preselectedOrderId' => $preselectedOrderId ?? null,
            'companyId' => (string) old('company_id', $invoice->company_id ?? ''),
            'orderMode' => old('order_mode', 'existing'),
            'orderId' => (string) old('order_id', $invoice->order_id ?? ''),
            'newOrderBaseValue' => old('new_order_base_value', ''),
            'newOrderTaxPercent' => old('new_order_tax_percent', '18'),
            'newOrderDescription' => old('new_order_description', ''),
            'template' => old('template', $invoice->template ?? 'classic'),
            'invoiceType' => old('invoice_type', $invoice->invoice_type ?? 'tax_invoice'),
            'gstType' => old('gst_type', $invoice->gst_type ?? 'gst'),
            'taxType' => old('tax_type', $invoice->tax_type ?? 'cgst_sgst'),
            'invoiceDate' => old('invoice_date', $invoice ? $invoice->invoice_date->format('Y-m-d') : now()->format('Y-m-d')),
            'dueDate' => old('due_date', $invoice?->due_date?->format('Y-m-d') ?? ''),
            'referenceNumber' => old('reference_number', $invoice->reference_number ?? ''),
            'otherReferences' => old('other_references', $invoice->other_references ?? ''),
            'deliveryNote' => old('delivery_note', $invoice->delivery_note ?? ''),
            'buyerOrderNumber' => old('buyer_order_number', $invoice->buyer_order_number ?? ''),
            'items' => old('items', $invoice
                ? $invoice->items->sortBy('sort_order')->values()->map(fn ($item) => [
                    'item_description' => $item->item_description,
                    'hsn_sac' => $item->hsn_sac,
                    'quantity' => (float) $item->quantity,
                    'rate' => (float) $item->rate,
                    'tax_percent' => (float) $item->tax_percent,
                ])->toArray()
                : [['item_description' => '', 'hsn_sac' => '', 'quantity' => 1, 'rate' => 0, 'tax_percent' => 18]]
            ),
        ];
    @endphp

    <script type="application/json" id="wizard-config">@json($wizardConfig)</script>

        <div class="py-8" x-data="invoiceWizard()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex items-center mb-8 overflow-x-auto pb-1">
                               <template x-for="(label, index) in ['Company', 'Type & GST', 'Sales Order', 'Line Items', 'Format']" :key="index">
                    <div class="flex items-center flex-1 last:flex-none">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0"
                                 :class="step > index + 1 ? 'bg-signal text-white' : (step === index + 1 ? 'bg-signal text-white' : 'bg-border text-ink-muted')">
                                <span x-show="step <= index + 1" x-text="index + 1"></span>
                                <svg x-show="step > index + 1" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium hidden sm:inline" :class="step === index + 1 ? 'text-ink' : 'text-ink-muted'" x-text="label"></span>
                        </div>
                        <div class="flex-1 h-px bg-border mx-2 sm:mx-4 last:hidden min-w-4"></div>
                    </div>
                </template>
            </div>

            @if ($errors->any())
                <div class="mb-6 px-4 py-3 bg-status-overdue/10 border border-status-overdue/20 text-status-overdue text-sm rounded-lg">
                    <p class="font-medium mb-1">Please fix the following before continuing:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @isset($randomNumberPrefix)
                <div class="mb-6 px-4 py-3 bg-signal/5 border border-signal/20 text-ink text-sm rounded-lg">
                    <p class="font-medium">Random invoice</p>
                    <p class="text-xs text-ink-muted mt-0.5">
                        This creates a real invoice with a number you enter (<span class="font-mono">{{ $randomNumberPrefix }}…</span>) instead of the next sequential one.
                        The regular invoice sequence is not affected. Everything else works exactly like a normal invoice.
                    </p>
                </div>
            @endisset

            <form method="POST" action="{{ $invoice ? route('invoices.update', $invoice) : (isset($randomNumberPrefix) ? route('invoices.random.store') : route('invoices.store')) }}" @submit="if (!validateCurrentStep()) $event.preventDefault()">
                @csrf
                @if ($invoice) @method('PUT') @endif
                @isset($randomNumberPrefix)
                    <input type="hidden" name="random_suffix" :value="randomSuffix">
                @endisset

                <input type="hidden" name="company_id" :value="companyId">
                <input type="hidden" name="order_mode" :value="orderMode">
                <input type="hidden" name="order_id" :value="orderMode === 'existing' ? orderId : ''">
                <input type="hidden" name="new_order_base_value" :value="orderMode === 'new' ? newOrderBaseValue : ''">
                <input type="hidden" name="new_order_tax_percent" :value="orderMode === 'new' ? newOrderTaxPercent : ''">
                <input type="hidden" name="new_order_description" :value="orderMode === 'new' ? newOrderDescription : ''">
                <input type="hidden" name="invoice_type" :value="invoiceType">
                <input type="hidden" name="gst_type" :value="gstType">
                <input type="hidden" name="tax_type" :value="gstType === 'gst' ? taxType : ''">
                <input type="hidden" name="invoice_date" :value="invoiceDate">
                <input type="hidden" name="due_date" :value="dueDate">
                <input type="hidden" name="reference_number" :value="referenceNumber">
                <input type="hidden" name="other_references" :value="otherReferences">
                <input type="hidden" name="delivery_note" :value="deliveryNote">
                <input type="hidden" name="buyer_order_number" :value="buyerOrderNumber">
                <input type="hidden" name="template" :value="template">

                <template x-for="(item, index) in items" :key="index">
                    <div>
                        <input type="hidden" :name="`items[${index}][item_description]`" :value="item.item_description">
                        <input type="hidden" :name="`items[${index}][hsn_sac]`" :value="item.hsn_sac">
                        <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                        <input type="hidden" :name="`items[${index}][rate]`" :value="item.rate">
                        <input type="hidden" :name="`items[${index}][tax_percent]`" :value="item.tax_percent">
                    </div>
                </template>

                {{-- STEP 1: Company --}}
                <div x-show="step === 1" x-cloak>
                    <div class="bg-surface border border-border rounded-xl shadow-sm shadow-black/[0.03] p-6">
                        <h3 class="text-sm font-semibold text-ink mb-1">Select Company</h3>
                        <p class="text-xs text-ink-muted mb-5">This is who the invoice will be billed to.</p>

                        @error('company_id') <p class="mb-3 text-sm text-status-overdue">{{ $message }}</p> @enderror
                        <p x-show="clientErrors.company" x-text="clientErrors.company" class="mb-3 text-sm text-status-overdue"></p>
                        <p x-show="isEditing" class="mb-4 text-xs text-status-pending bg-status-pending/10 border border-status-pending/20 rounded-lg px-3 py-2">
                            Company can't be changed after an invoice is created.
                        </p>

                        <div class="relative mb-4">
                            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                            </svg>
                            <input type="text" x-model="companySearch" placeholder="Search companies by name or GSTIN..."
                                   class="w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal pl-9 pr-3 py-2.5">
                        </div>

                        <p x-show="companies.length > 0 && filteredCompanies.length === 0" class="text-sm text-ink-muted py-4 text-center">
                            No companies match "<span x-text="companySearch"></span>".
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <template x-for="company in filteredCompanies" :key="company.id">
                                <button type="button" @click="!isEditing && (companyId = String(company.id))" :disabled="isEditing"
                                        class="flex items-center gap-3 p-4 rounded-lg border text-left transition"
                                        :class="[companyId === String(company.id) ? 'border-signal ring-1 ring-signal bg-signal/5' : 'border-border hover:border-ink-muted/40', isEditing && companyId !== String(company.id) ? 'opacity-40' : '', isEditing ? 'cursor-not-allowed' : '']">
                                    <div class="w-10 h-10 rounded-lg bg-canvas border border-border flex items-center justify-center shrink-0 overflow-hidden">
                                        <span class="text-sm font-semibold text-ink-muted" x-text="company.name.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-ink truncate" x-text="company.name"></p>
                                        <p class="text-xs text-ink-muted font-mono mt-0.5" x-text="company.gst_number || 'No GSTIN'"></p>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- STEP 2: Invoice Type & GST --}}
                <div x-show="step === 2" x-cloak>
                    <div class="bg-surface border border-border rounded-xl shadow-sm shadow-black/[0.03] p-6 space-y-6">
                        @isset($randomNumberPrefix)
                            <div>
                                <h3 class="text-sm font-semibold text-ink mb-1">Invoice Number</h3>
                                <p class="text-xs text-ink-muted mb-3">Leave blank to have one generated automatically, or enter your own 1–12 letters/digits. It must not match another random invoice number.</p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-sm text-ink-muted">{{ $randomNumberPrefix }}</span>
                                    <input type="text" x-model="randomSuffix" maxlength="12" placeholder="auto"
                                           class="w-40 rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2 font-mono uppercase">
                                    <button type="button" @click="randomSuffix = String(Math.floor(100000 + Math.random() * 900000))"
                                            class="text-sm font-medium text-signal hover:text-signal-hover">Generate</button>
                                </div>
                                @error('random_suffix') <p class="mt-2 text-sm text-status-overdue">{{ $message }}</p> @enderror
                            </div>
                        @endisset

                        <div>
                            <h3 class="text-sm font-semibold text-ink mb-3">Invoice Type</h3>
                            <div class="inline-flex rounded-lg border border-border p-1 bg-canvas">
                                <button type="button" @click="invoiceType = 'invoice'"
                                        class="px-4 py-2 text-sm font-medium rounded-md transition"
                                        :class="invoiceType === 'invoice' ? 'bg-surface text-ink shadow-sm' : 'text-ink-muted'">
                                    Invoice (Unpaid)
                                </button>
                                <button type="button" @click="invoiceType = 'tax_invoice'"
                                        class="px-4 py-2 text-sm font-medium rounded-md transition"
                                        :class="invoiceType === 'tax_invoice' ? 'bg-surface text-ink shadow-sm' : 'text-ink-muted'">
                                    Tax Invoice (Paid)
                                </button>
                            </div>
                            @error('invoice_type') <p class="mt-2 text-sm text-status-overdue">{{ $message }}</p> @enderror
                        </div>

                            <div>
                            <h3 class="text-sm font-semibold text-ink mb-3">GST Type</h3>
                            <p x-show="invoiceType === 'invoice'" class="text-xs text-ink-muted mb-2">Unpaid invoices don't include tax — switch to "Tax Invoice" to charge GST.</p>
                            <div class="inline-flex rounded-lg border border-border p-1 bg-canvas" :class="invoiceType === 'invoice' ? 'opacity-50 pointer-events-none' : ''">
                                <button type="button" @click="gstType = 'gst'"
                                        class="px-4 py-2 text-sm font-medium rounded-md transition"
                                        :class="gstType === 'gst' ? 'bg-surface text-ink shadow-sm' : 'text-ink-muted'">
                                    GST
                                </button>
                                <button type="button" @click="gstType = 'non_gst'"
                                        class="px-4 py-2 text-sm font-medium rounded-md transition"
                                        :class="gstType === 'non_gst' ? 'bg-surface text-ink shadow-sm' : 'text-ink-muted'">
                                    Non-GST
                                </button>
                            </div>
                            @error('gst_type') <p class="mt-2 text-sm text-status-overdue">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="gstType === 'gst'" x-cloak>
                            <h3 class="text-sm font-semibold text-ink mb-3">Tax Type</h3>
                            <div class="inline-flex rounded-lg border border-border p-1 bg-canvas">
                                <button type="button" @click="taxType = 'cgst_sgst'"
                                        class="px-4 py-2 text-sm font-medium rounded-md transition"
                                        :class="taxType === 'cgst_sgst' ? 'bg-surface text-ink shadow-sm' : 'text-ink-muted'">
                                    CGST + SGST (Same State)
                                </button>
                                <button type="button" @click="taxType = 'igst'"
                                        class="px-4 py-2 text-sm font-medium rounded-md transition"
                                        :class="taxType === 'igst' ? 'bg-surface text-ink shadow-sm' : 'text-ink-muted'">
                                    IGST (Different State)
                                </button>
                            </div>
                            @error('tax_type') <p class="mt-2 text-sm text-status-overdue">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                            <div>
                                <x-input-label value="Invoice Date" />
                                <input type="date" x-model="invoiceDate"
                                       class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                                @error('invoice_date') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                                <p x-show="clientErrors.invoiceDate" x-text="clientErrors.invoiceDate" class="mt-1.5 text-sm text-status-overdue"></p>
                            </div>
                            <div>
                                <x-input-label value="Due Date" />
                                <input type="date" x-model="dueDate" :disabled="invoiceType === 'tax_invoice'"
                                       class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5 disabled:bg-canvas disabled:text-ink-muted disabled:cursor-not-allowed">
                                <p x-show="invoiceType === 'tax_invoice'" class="mt-1.5 text-xs text-ink-muted">Not applicable — this invoice type is already paid.</p>
                                @error('due_date') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                                <p x-show="clientErrors.dueDate" x-text="clientErrors.dueDate" class="mt-1.5 text-sm text-status-overdue"></p>
                            </div>
                            <div>
                                <x-input-label value="Reference Number" />
                                <input type="text" x-model="referenceNumber"
                                       class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                            </div>
                            <div>
                                <x-input-label value="Other References" />
                                <input type="text" x-model="otherReferences"
                                       class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                                @error('other_references') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <x-input-label value="Delivery Note" />
                                <input type="text" x-model="deliveryNote"
                                       class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                            </div>
                            <div>
                                <x-input-label value="Buyer's Order Number" />
                                <input type="text" x-model="buyerOrderNumber"
                                       class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 3: Sales Order --}}
                <div x-show="step === 3" x-cloak>
                    <div class="bg-surface border border-border rounded-xl shadow-sm shadow-black/[0.03] p-6">
                        @if ($invoice)
                            {{-- Locked once the invoice exists, same treatment as Company --}}
                            <h3 class="text-sm font-semibold text-ink mb-1">Sales Order</h3>
                            <p class="text-xs text-ink-muted mb-4">Locked — the sales order linked to an invoice can't be changed after it's created.</p>
                            @if ($invoice->order)
                                <div class="px-4 py-3 bg-canvas rounded-lg border border-border">
                                    <div class="flex items-center justify-between mb-3">
                                        <p class="text-sm font-medium text-ink font-mono">{{ $invoice->order->order_number }}</p>
                                        <a href="{{ route('orders.show', $invoice->order) }}" class="text-sm font-medium text-signal hover:text-signal-hover">View Order →</a>
                                    </div>
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <p class="text-xs text-ink-muted">Order Total</p>
                                            <p class="text-sm font-semibold text-ink mt-0.5 font-mono">&#8377;{{ number_format($invoice->order->total_value, 2) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-ink-muted">Already Billed</p>
                                            <p class="text-sm font-semibold text-signal mt-0.5 font-mono">&#8377;{{ number_format($invoice->order->amount_billed, 2) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-ink-muted">Remaining (excl. this draft)</p>
                                            <p class="text-sm font-semibold text-status-pending mt-0.5 font-mono">&#8377;{{ number_format($invoice->order->amount_remaining, 2) }}</p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-ink-muted px-4 py-3 bg-canvas rounded-lg border border-border">
                                    No sales order was linked to this invoice (it was created before this feature existed).
                                </p>
                            @endif
                        @else
                            <h3 class="text-sm font-semibold text-ink mb-1">Sales Order</h3>
                            <p class="text-xs text-ink-muted mb-5">Every invoice bills against a sales order — pick an existing one for this company or start a new one.</p>

                            <div class="inline-flex rounded-lg border border-border p-1 bg-canvas mb-5">
                                <button type="button" @click="ordersForCompany.length > 0 && (orderMode = 'existing')"
                                        :disabled="ordersForCompany.length === 0"
                                        class="px-4 py-2 text-sm font-medium rounded-md transition"
                                        :class="[orderMode === 'existing' ? 'bg-surface text-ink shadow-sm' : 'text-ink-muted', ordersForCompany.length === 0 ? 'opacity-40 cursor-not-allowed' : '']">
                                    Link Existing Order
                                </button>
                                <button type="button" @click="orderMode = 'new'"
                                        class="px-4 py-2 text-sm font-medium rounded-md transition"
                                        :class="orderMode === 'new' ? 'bg-surface text-ink shadow-sm' : 'text-ink-muted'">
                                    Create New Order
                                </button>
                            </div>

                            <div x-show="orderMode === 'existing'">
                                <p x-show="ordersForCompany.length === 0" class="text-sm text-ink-muted mb-2">
                                    No open sales orders for this company yet — switch to "Create New Order".
                                </p>
                                <div class="space-y-2">
                                    <template x-for="order in ordersForCompany" :key="order.id">
                                        <button type="button" @click="orderId = String(order.id)"
                                                class="flex items-center justify-between w-full px-4 py-3 rounded-lg border text-left transition"
                                                :class="String(orderId) === String(order.id) ? 'border-signal ring-1 ring-signal bg-signal/5' : 'border-border hover:border-ink-muted/40'">
                                            <div>
                                                <p class="text-sm font-medium text-ink font-mono" x-text="order.order_number"></p>
                                                <p class="text-xs text-ink-muted mt-0.5">
                                                    Remaining: <span x-text="formatMoney(order.amount_remaining)"></span>
                                                    of <span x-text="formatMoney(order.total_value)"></span> (incl. GST)
                                                </p>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                                @error('order_id') <p class="mt-2 text-sm text-status-overdue">{{ $message }}</p> @enderror
                                <p x-show="clientErrors.order && orderMode === 'existing'" x-text="clientErrors.order" class="mt-2 text-sm text-status-overdue"></p>
                            </div>

                            <div x-show="orderMode === 'new'" class="space-y-5">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <x-input-label value="Base Order Value (₹, excl. GST)" />
                                        <input type="number" step="0.01" min="0.01" x-model="newOrderBaseValue"
                                            class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5 font-mono">
                                        @error('new_order_base_value') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                                        <p x-show="clientErrors.order && orderMode === 'new'" x-text="clientErrors.order" class="mt-1.5 text-sm text-status-overdue"></p>
                                    </div>
                                    <div>
                                        <x-input-label value="Tax %" />
                                        <input type="number" step="0.01" min="0" max="100" x-model="newOrderTaxPercent" :disabled="gstType === 'non_gst'"
                                            class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5 font-mono disabled:bg-canvas disabled:text-ink-muted">
                                        <p x-show="gstType === 'non_gst'" class="mt-1.5 text-xs text-ink-muted">Locked at 0% — this order is being created from a non-GST invoice.</p>
                                        @error('new_order_tax_percent') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <p class="text-xs text-ink-muted">
                                    Total order value (incl. GST): <span class="font-mono text-ink" x-text="formatMoney(newOrderComputedTotal)"></span> — this is entered as base + tax, not the total directly, and becomes the order's capacity.
                                </p>
                                <div>
                                    <x-input-label value="Order Description (optional)" />
                                    <textarea x-model="newOrderDescription" rows="3"
                                            class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5"></textarea>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- STEP 4: Line Items --}}
                <div x-show="step === 4" x-cloak>
                    <div x-show="orderCapacity !== null" x-cloak
                         class="mb-4 px-4 py-3 rounded-lg border text-sm"
                         :class="exceedsOrderCapacity ? 'bg-status-overdue/10 border-status-overdue/20' : 'bg-signal/5 border-signal/20'">
                        <p class="text-xs font-medium uppercase tracking-wide mb-2" :class="exceedsOrderCapacity ? 'text-status-overdue' : 'text-ink-muted'">
                            Billing against this sales order
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <p class="text-xs text-ink-muted">Sales Order Total <span class="whitespace-nowrap">(incl. GST)</span></p>
                                <p class="font-mono font-semibold text-ink" x-text="formatMoney(orderTotalValue)"></p>
                            </div>
                            <div>
                                <p class="text-xs text-ink-muted">Already Billed <span class="whitespace-nowrap">(incl. GST)</span></p>
                                <p class="font-mono font-semibold text-signal" x-text="formatMoney(orderBilledValue)"></p>
                            </div>
                            <div>
                                <p class="text-xs text-ink-muted">Remaining <span class="whitespace-nowrap">(incl. GST)</span></p>
                                <p class="font-mono font-semibold" :class="exceedsOrderCapacity ? 'text-status-overdue' : 'text-ink'" x-text="formatMoney(orderCapacity)"></p>
                            </div>
                            <div>
                                <p class="text-xs text-ink-muted">After This Invoice <span class="whitespace-nowrap">(incl. GST)</span></p>
                                <p class="font-mono font-semibold" :class="exceedsOrderCapacity ? 'text-status-overdue' : 'text-ink'" x-text="formatMoney(orderCapacity - grandTotal)"></p>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-ink-muted">
                            These amounts include GST. Enter line-item rates <span class="font-medium text-ink">excluding GST</span> — GST is added automatically and the Total below is what counts against the remaining amount.
                            <template x-if="remainingExGst !== null">
                                <span>Remaining is about <span class="font-mono font-medium text-ink" x-text="formatMoney(remainingExGst)"></span> excluding GST (at <span x-text="orderTaxPercent"></span>%).</span>
                            </template>
                        </p>
                    </div>

                    <div class="bg-surface border border-border rounded-xl shadow-sm shadow-black/[0.03] overflow-hidden">
                        <div class="px-6 py-4 border-b border-border flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-ink">Line Items</h3>
                                <p class="text-xs text-ink-muted mt-0.5">Amounts are recalculated automatically on save.</p>
                            </div>
                            <button type="button" @click="addItem()"
                                    class="text-sm font-medium text-signal hover:text-signal-hover">
                                + Add Row
                            </button>
                        </div>
                        @error('items') <p class="px-6 pt-4 text-sm text-status-overdue">{{ $message }}</p> @enderror
                        <p x-show="clientErrors.items" x-text="clientErrors.items" class="px-6 pt-4 text-sm text-status-overdue"></p>

                        <div>
                            <table class="w-full text-sm table-fixed">
                                <thead class="bg-canvas border-b border-border">
                                    <tr class="text-left text-ink-muted">
                                        <th class="px-4 py-2.5 font-medium w-auto">Description</th>
                                        <th class="px-2 py-2.5 font-medium w-24">HSN/SAC</th>
                                        <th class="px-2 py-2.5 font-medium w-20">Qty</th>
                                        <th class="px-2 py-2.5 font-medium w-36">Rate <span class="font-normal text-xs">(excl. GST)</span></th>
                                        <th class="px-2 py-2.5 font-medium w-20">Tax %</th>
                                        <th class="px-2 py-2.5 font-medium w-28 text-right">Amount</th>
                                        <th class="px-2 py-2.5 w-8"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border">
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr>
                                            <td class="px-4 py-2">
                                                <input type="text" x-model="item.item_description"
                                                       class="w-full rounded-md border-border text-sm px-2.5 py-1.5 focus:border-signal focus:ring-1 focus:ring-signal">
                                            </td>
                                            <td class="px-2 py-2">
                                                <input type="text" x-model="item.hsn_sac"
                                                       class="w-full rounded-md border-border text-sm px-2 py-1.5 font-mono focus:border-signal focus:ring-1 focus:ring-signal">
                                            </td>
                                            <td class="px-2 py-2">
                                                <input type="number" step="0.01" x-model.number="item.quantity"
                                                       class="w-full rounded-md border-border text-sm px-2 py-1.5 font-mono focus:border-signal focus:ring-1 focus:ring-signal [appearance:textfield] [-moz-appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                            </td>
                                            <td class="px-2 py-2">
                                                <input type="number" step="0.01" x-model.number="item.rate"
                                                       class="w-full rounded-md border-border text-sm px-2 py-1.5 font-mono focus:border-signal focus:ring-1 focus:ring-signal [appearance:textfield] [-moz-appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                            </td>
                                            <td class="px-2 py-2">
                                                <input type="number" step="0.01" x-model.number="item.tax_percent" :disabled="gstType === 'non_gst'"
                                                       class="w-full rounded-md border-border text-sm px-2 py-1.5 font-mono focus:border-signal focus:ring-1 focus:ring-signal disabled:bg-canvas disabled:text-ink-muted [appearance:textfield] [-moz-appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                            </td>
                                            <td class="px-2 py-2 text-right font-mono text-ink break-words" x-text="formatMoney(itemAmount(item))"></td>
                                            <td class="px-2 py-2 text-right">
                                                <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                                        class="text-status-overdue/70 hover:text-status-overdue">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="px-6 py-4 border-t border-border bg-canvas/50 flex justify-end">
                            <div class="w-auto min-w-[16rem] space-y-1.5">
                                <div class="flex justify-between gap-4 text-sm">
                                    <span class="text-ink-muted">Subtotal</span>
                                    <span class="font-mono text-ink whitespace-nowrap" x-text="formatMoney(subtotal)"></span>
                                </div>
                                <div class="flex justify-between gap-4 text-sm">
                                    <span class="text-ink-muted">Tax</span>
                                    <span class="font-mono text-ink whitespace-nowrap" x-text="formatMoney(taxTotal)"></span>
                                </div>
                                <div class="flex justify-between gap-4 text-sm font-semibold pt-1.5 border-t border-border">
                                    <span class="text-ink">Total</span>
                                    <span class="font-mono text-ink whitespace-nowrap" x-text="formatMoney(grandTotal)"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="exceedsOrderCapacity" x-cloak
                         class="mt-4 px-4 py-3 bg-status-overdue/10 border border-status-overdue/20 text-status-overdue text-sm rounded-lg">
                        This invoice's total (<span x-text="formatMoney(grandTotal)"></span>) exceeds the order's remaining value of <span x-text="formatMoney(orderCapacity)"></span>. Reduce the line items, or go back to Step 3 and choose a different/larger order.
                    </div>
                </div>

                {{-- STEP 5: Format --}}
                <div x-show="step === 5" x-cloak>
                    <div class="bg-surface border border-border rounded-xl shadow-sm shadow-black/[0.03] p-6">
                        <h3 class="text-sm font-semibold text-ink mb-1">Invoice Format</h3>
                        <p class="text-xs text-ink-muted mb-5">Choose how this invoice will look when viewed, printed, or downloaded.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <button type="button" @click="template = 'classic'"
                                    class="text-left p-4 rounded-lg border transition"
                                    :class="template === 'classic' ? 'border-signal ring-1 ring-signal bg-signal/5' : 'border-border hover:border-ink-muted/40'">
                                <p class="text-sm font-medium text-ink mb-2">Classic</p>
                                <div class="border border-border rounded bg-canvas p-2 text-[8px] leading-tight text-ink-muted font-mono">
                                    <div class="border-b border-border pb-1 mb-1 font-semibold text-ink">TAX INVOICE</div>
                                    <div>Buyer (Bill to)</div>
                                    <div class="border-t border-b border-border my-1 py-1">Description · HSN · Qty · Rate · Amount</div>
                                    <div>CGST / SGST / IGST</div>
                                    <div>Amount in words</div>
                                </div>
                                <p class="text-xs text-ink-muted mt-2">Bordered, document-style — matches a traditional GST tax invoice.</p>
                            </button>

                            <button type="button" @click="template = 'modern'"
                                    class="text-left p-4 rounded-lg border transition"
                                    :class="template === 'modern' ? 'border-signal ring-1 ring-signal bg-signal/5' : 'border-border hover:border-ink-muted/40'">
                                <p class="text-sm font-medium text-ink mb-2">Modern</p>
                                <div class="border border-border rounded bg-canvas p-2 text-[8px] leading-tight text-ink-muted font-mono">
                                    <div class="flex justify-between font-semibold text-ink"><span>COMPANY NAME</span><span>TAX INVOICE</span></div>
                                    <div class="grid grid-cols-2 gap-1 mt-1 pt-1 border-t border-border">
                                        <span>Invoice Info</span><span>Buyer Info</span>
                                    </div>
                                    <div class="mt-1 pt-1 border-t border-border">Items table</div>
                                    <div class="mt-1 pt-1 border-t border-border">Tax Summary · Grand Total</div>
                                </div>
                                <p class="text-xs text-ink-muted mt-2">Clean two-column layout with a tax summary block.</p>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Navigation --}}
                <div class="sticky bottom-0 mt-6 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-4 bg-canvas/95 backdrop-blur border-t border-border flex items-center justify-between">
                    <button type="button" @click="step--" x-show="step > 1"
                            class="px-4 py-2.5 text-sm font-medium text-ink-muted hover:text-ink transition">
                        ← Back
                    </button>
                    <span x-show="step === 1"></span>

                    <button type="button" @click="goNext()" x-show="step < 5"
                            class="inline-flex items-center px-4 py-2.5 bg-signal text-white text-sm font-medium rounded-lg hover:bg-signal-hover transition">
                        Continue
                    </button>
                    <x-primary-button x-show="step === 5">
                        <span x-text="isEditing ? 'Save Changes' : 'Save Draft'"></span>
                    </x-primary-button>
                </div>

            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function invoiceWizard() {
            const config = JSON.parse(document.getElementById('wizard-config').textContent);
            return {
                companies: config.companies,
                step: config.step,
                isEditing: config.isEditing,
                randomSuffix: config.randomSuffix,
                preselectedOrderId: config.preselectedOrderId,
                companyId: config.companyId,
                orderMode: config.orderMode,
                orderId: config.orderId,
                newOrderBaseValue: config.newOrderBaseValue,
                newOrderTaxPercent: config.newOrderTaxPercent,
                newOrderDescription: config.newOrderDescription,
                invoiceType: config.invoiceType,
                gstType: config.gstType,
                taxType: config.taxType,
                invoiceDate: config.invoiceDate,
                dueDate: config.dueDate,
                referenceNumber: config.referenceNumber,
                otherReferences: config.otherReferences,
                deliveryNote: config.deliveryNote,
                buyerOrderNumber: config.buyerOrderNumber,
                template: config.template,
                items: config.items,
                companySearch: '',

                clientErrors: {},

                validateCurrentStep() {
                    this.clientErrors = {};

                    if (this.step === 1) {
                        if (!this.companyId) this.clientErrors.company = 'Please select a company.';
                    }

                    if (this.step === 2) {
                        if (!this.invoiceDate) this.clientErrors.invoiceDate = 'Invoice date is required.';
                        if (this.dueDate && this.invoiceDate && this.dueDate < this.invoiceDate) {
                            this.clientErrors.dueDate = 'Due date cannot be before the invoice date.';
                        }
                    }

                    if (this.step === 3 && !this.isEditing) {
                        if (this.orderMode === 'existing' && !this.orderId) {
                            this.clientErrors.order = 'Please select a sales order.';
                        }
                        if (this.orderMode === 'new' && (!this.newOrderBaseValue || parseFloat(this.newOrderBaseValue) <= 0)) {
                                this.clientErrors.order = 'Please enter the base order value.';
                            }
                    }

                    if (this.step === 4) {
                        const hasInvalidItem = this.items.some(item =>
                            !item.item_description.trim() || !item.quantity || item.rate === '' || item.rate === null
                        );
                        if (hasInvalidItem) {
                            this.clientErrors.items = 'Every line item needs a description, quantity, and rate.';
                        }
                    }

                    return Object.keys(this.clientErrors).length === 0;
                },

                goNext() {
                    if (this.validateCurrentStep()) {
                        this.step++;
                    }
                },

                get filteredCompanies() {
                    const q = this.companySearch.trim().toLowerCase();
                    if (!q) return this.companies;
                    return this.companies.filter(c =>
                        c.name.toLowerCase().includes(q) ||
                        (c.gst_number && c.gst_number.toLowerCase().includes(q))
                    );
                },

                get ordersForCompany() {
                    const company = this.companies.find(c => String(c.id) === this.companyId);
                    return company?.sales_orders ?? [];
                },

                init() {
                    // Force tax to 0% immediately if the invoice already loaded as "Invoice (Unpaid)"
                    // — e.g. editing a draft that was saved as unpaid.
                    if (this.invoiceType === 'invoice') {
                        this.gstType = 'non_gst';
                    }
                    if (this.gstType === 'non_gst') {
                        this.items.forEach(item => item.tax_percent = 0);
                        this.newOrderTaxPercent = '0';
                    }

                    // Unpaid invoices are always non-GST.
                    this.$watch('invoiceType', (value) => {
                        if (value === 'invoice') {
                            this.gstType = 'non_gst';
                        }
                        if (value === 'tax_invoice') {
                            this.dueDate = '';
                        }
                    });

                    // Whenever GST Type becomes Non-GST — whether because the invoice is
                    // Unpaid, or a Paid/Tax Invoice was explicitly set to Non-GST — tax is
                    // locked at 0%, for every line item and for a brand-new sales order.
                    this.$watch('gstType', (value) => {
                        if (value === 'non_gst') {
                            this.items.forEach(item => item.tax_percent = 0);
                            this.newOrderTaxPercent = '0';
                        }
                    });

                    // Deep-link from "Bill Against This Order" on a Sales Order's page:
                    // find which company that order belongs to and preselect it.
                    if (this.preselectedOrderId && !this.isEditing) {
                        for (const company of this.companies) {
                            const match = (company.sales_orders || []).find(o => String(o.id) === String(this.preselectedOrderId));
                            if (match) {
                                this.companyId = String(company.id);
                                this.orderMode = 'existing';
                                this.orderId = String(match.id);
                                break;
                            }
                        }
                    }

                    // Reset the order selection whenever the company changes, and default to
                    // "Create New Order" if this company has no open orders to link to.
                    this.$watch('companyId', () => {
                        if (!this.isEditing) {
                            this.orderId = '';
                            this.orderMode = this.ordersForCompany.length > 0 ? 'existing' : 'new';
                        }
                    });
                },

                itemAmount(item) {
                    const qty = parseFloat(item.quantity) || 0;
                    const rate = parseFloat(item.rate) || 0;
                    return qty * rate;
                },

                get subtotal() {
                    return this.items.reduce((sum, item) => sum + this.itemAmount(item), 0);
                },

                get taxTotal() {
                    return this.items.reduce((sum, item) => {
                        const tax = parseFloat(item.tax_percent) || 0;
                        return sum + (this.itemAmount(item) * tax / 100);
                    }, 0);
                },

                get grandTotal() {
                    return this.subtotal + this.taxTotal;
                },

                get newOrderComputedTotal() {
                    const base = parseFloat(this.newOrderBaseValue) || 0;
                    const tax = parseFloat(this.newOrderTaxPercent) || 0;
                    return base + (base * tax / 100);
                },

                get selectedOrder() {
                    if (this.orderMode !== 'existing') return null;
                    return this.ordersForCompany.find(o => String(o.id) === String(this.orderId)) ?? null;
                },

                get orderCapacity() {
                    if (this.orderMode === 'existing') {
                        return this.selectedOrder ? parseFloat(this.selectedOrder.amount_remaining) : null;
                    }
                    if (this.orderMode === 'new') {
                        return this.newOrderBaseValue ? this.newOrderComputedTotal : null;
                    }
                    return null;
                },

                get orderTotalValue() {
                    if (this.orderMode === 'existing') {
                        return this.selectedOrder ? parseFloat(this.selectedOrder.total_value) : 0;
                    }
                    if (this.orderMode === 'new') {
                        return this.newOrderComputedTotal;
                    }
                    return 0;
                },

                get orderBilledValue() {
                    if (this.orderMode === 'existing' && this.selectedOrder) {
                        return parseFloat(this.selectedOrder.amount_billed);
                    }
                    return 0;
                },

                // The order's GST rate, used only to show the remaining amount without GST.
                get orderTaxPercent() {
                    if (this.orderMode === 'new') return parseFloat(this.newOrderTaxPercent) || 0;
                    return this.selectedOrder ? (parseFloat(this.selectedOrder.tax_percent) || 0) : 0;
                },

                get remainingExGst() {
                    if (this.orderCapacity === null || this.gstType === 'non_gst' || this.orderTaxPercent <= 0) return null;
                    return Math.max(0, this.orderCapacity) / (1 + this.orderTaxPercent / 100);
                },

                get exceedsOrderCapacity() {
                    return this.orderCapacity !== null && this.grandTotal > this.orderCapacity + 0.01;
                },

                formatMoney(value) {
                    return '₹' + Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                addItem() {
                    this.items.push({ item_description: '', hsn_sac: '', quantity: 1, rate: 0, tax_percent: this.gstType === 'non_gst' ? 0 : 18 });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },
            };
        }
    </script>
    @endpush
</x-app-layout>

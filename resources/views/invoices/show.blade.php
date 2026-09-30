<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('invoices.history') }}" class="text-ink-muted hover:text-ink">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-ink leading-tight">{{ $invoice->invoice_number }}</h2>
        </div>
    </x-slot>

    <style>
        .inv-table, .inv-table th, .inv-table td { border: 1px solid #333; border-collapse: collapse; }
        .inv-table th, .inv-table td { padding: 6px 8px; }
        .inv-table .plain, .inv-table .plain td { border: none !important; padding: 0 !important; }
        @media print {
            .inv-table, .inv-table th, .inv-table td { border-color: #000; }
        }

        /* Below sm (640px): the Particulars and Tax tables have too many fixed-width
           columns to fit a phone screen, so turn each row into a stacked label/value
           card instead of forcing horizontal scroll. */
        @media (max-width: 639px) {
            .inv-table-cards, .inv-table-cards tbody, .inv-table-cards tr, .inv-table-cards td {
                display: block;
                width: 100% !important;
            }
            .inv-table-cards thead { display: none; }
            .inv-table-cards tr {
                border: 1px solid #333;
                border-radius: 8px;
                padding: 8px 10px;
                margin-bottom: 8px;
            }
            .inv-table-cards tr:last-child { margin-bottom: 0; }
            .inv-table-cards td {
                border: none !important;
                padding: 3px 0 !important;
                text-align: left !important;
            }
            .inv-table-cards td[data-label]::before {
                content: attr(data-label);
                display: block;
                font-size: 10px;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: #64748b;
            }
        }
    </style>

    <div class="py-8 print:py-0">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 print:px-0 print:max-w-none">

            @if (session('status'))
                <div class="mb-4 px-4 py-3 bg-status-paid/10 border border-status-paid/20 text-status-paid text-sm rounded-lg print:hidden">
                    {{ session('status') }}
                </div>
            @endif

            <div x-data="{ showEmailForm: false }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 print:hidden">
                @php
                    $statusStyles = [
                        'draft' => 'bg-status-pending/10 text-status-pending',
                        'finalized' => 'bg-signal/10 text-signal',
                        'sent' => 'bg-status-paid/10 text-status-paid',
                        'archived' => 'bg-border text-ink-muted',
                    ];
                @endphp
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusStyles[$invoice->status] ?? 'bg-border text-ink-muted' }}">
                    {{ ucfirst($invoice->status) }}
                </span>

               <div class="flex items-center gap-2 flex-wrap">
                    @if ($invoice->status === 'draft')
                        <form method="POST" action="{{ route('invoices.finalize', $invoice) }}"
                            onsubmit="return confirm('Finalize this invoice? Once finalized, it can no longer be edited.');">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-status-paid text-white text-sm font-medium rounded-lg hover:bg-status-paid/90 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Finalize
                            </button>
                        </form>
                        <a href="{{ route('invoices.edit', $invoice) }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-surface border border-border text-ink text-sm font-medium rounded-lg hover:bg-canvas transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                            </svg>
                            Edit
                        </a>
                        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}"
                            onsubmit="return confirm('Delete this draft invoice? This cannot be undone.');">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-surface border border-border text-status-overdue text-sm font-medium rounded-lg hover:bg-status-overdue/10 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                                Delete
                            </button>
                        </form>
                    @endif
                    @if (in_array($invoice->status, ['finalized', 'sent']))
                        <button type="button" @click="showEmailForm = !showEmailForm"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-surface border border-border text-ink text-sm font-medium rounded-lg hover:bg-canvas transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                            </svg>
                            Send Email
                        </button>
                    @endif
                    <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-surface border border-border text-ink text-sm font-medium rounded-lg hover:bg-canvas transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75v6.75m0 0l-3-3m3 3l3-3m-8.25 6h13.5A2.25 2.25 0 0021 18.75V5.25A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25v13.5A2.25 2.25 0 005.25 21z" />
                        </svg>
                        View PDF
                    </a>

                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-signal text-white text-sm font-medium rounded-lg hover:bg-signal-hover transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                    </svg>
                    Print / Save as PDF
                    </button>
                </div>
            </div>

            <div x-show="showEmailForm" x-cloak x-data="{
                    sentTo: '{{ $invoice->buyer_email ?? '' }}',
                    cc: '',
                    bcc: '',
                    subject: 'Invoice {{ $invoice->invoice_number }} from {{ $organization->name }}',
                }"
                class="mb-4 bg-surface border border-border rounded-xl p-6 print:hidden">
                <h3 class="text-sm font-semibold text-ink mb-4">Send Invoice by Email</h3>
                <form method="POST" action="{{ route('invoices.send-email', $invoice) }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label value="To" />
                        <input type="email" name="sent_to" x-model="sentTo" required
                               class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                        @error('sent_to') <p class="mt-1.5 text-sm text-status-overdue">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="CC (comma-separated)" />
                            <input type="text" name="cc" x-model="cc"
                                   class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                        </div>
                        <div>
                            <x-input-label value="BCC (comma-separated)" />
                            <input type="text" name="bcc" x-model="bcc"
                                   class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                        </div>
                    </div>
                    <div>
                        <x-input-label value="Subject" />
                        <input type="text" name="subject" x-model="subject"
                               class="mt-1.5 block w-full rounded-lg border-border bg-white text-ink text-sm shadow-sm focus:border-signal focus:ring-1 focus:ring-signal px-3 py-2.5">
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showEmailForm = false"
                                class="px-4 py-2.5 text-sm font-medium text-ink-muted hover:text-ink transition">
                            Cancel
                        </button>
                        <x-primary-button>Queue Email</x-primary-button>
                    </div>
                </form>
            </div>
            </div>

            {{-- Invoice document --}}
            <div class="bg-surface border border-border rounded-xl p-4 sm:p-8 print:border-0 print:rounded-none print:p-0 text-[13px] leading-snug">

                <p class="text-center text-base font-bold tracking-wide uppercase mb-3">
                   {{ $invoice->invoice_type === 'tax_invoice' ? 'Tax Invoice' : 'Proforma Invoice' }}
                </p>

                @php
                    // GSTIN only appears on a GST-charged (tax) invoice. PAN is never
                    // shown on the invoice, for either party, in any case.
                    $showGst = $invoice->gst_type === 'gst';
                @endphp

                <table class="inv-table w-full">
                    {{-- Company header + Invoice No/Date --}}
                    <tr>
                        <td class="w-1/2 align-top">
                            <div class="flex items-start gap-3">
                                @if ($organization->logo_path && \App\Models\Setting::get('pdf_show_logo', '1') === '1')
                                    <img src="{{ Storage::url($organization->logo_path) }}" class="w-20 h-20 object-contain shrink-0">
                                @endif
                                <div>
                                    <p class="font-bold text-sm">{{ $organization->name }}</p>
                                    <p class="text-ink-muted whitespace-pre-line">{{ $organization->address }}</p>
                                    @if ($showGst && $organization->gst_number)
                                        <p>GSTIN/UIN: <span class="font-mono">{{ $organization->gst_number }}</span></p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="w-1/2 align-top">
                            <table class="w-full plain">
                                <tr>
                                    <td class="text-ink-muted pb-1">Invoice No.</td>
                                    <td class="text-ink-muted pb-1">Dated</td>
                                </tr>
                                <tr>
                                    <td class="font-mono font-medium">{{ $invoice->invoice_number }}</td>
                                    <td class="font-medium">{{ $invoice->invoice_date->format('d-M-y') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Reference / Other References / Buyer's Order No --}}
                    <tr>
                        <td class="align-top">
                            <p class="text-ink-muted">Reference No. &amp; Date</p>
                            <p class="font-medium">{{ $invoice->reference_number ?? '—' }}</p>
                            @if ($invoice->other_references)
                                <p class="text-ink-muted mt-1.5">Other References</p>
                                <p class="font-medium">{{ $invoice->other_references }}</p>
                            @endif
                        </td>
                        <td class="align-top">
                            <p class="text-ink-muted">Buyer's Order No.</p>
                            <p class="font-medium">{{ $invoice->buyer_order_number ?? '—' }}</p>
                            @if ($invoice->due_date && $invoice->amount_outstanding > 0)
                                <p class="text-ink-muted mt-1.5">Due Date</p>
                                <p class="font-medium">{{ $invoice->due_date->format('d-M-y') }}</p>
                            @endif
                        </td>
                    </tr>

                    {{-- Buyer (Bill to) --}}
                    <tr>
                        <td colspan="2" class="align-top">
                            <p class="text-ink-muted uppercase text-[11px] tracking-wide mb-1">Buyer (Bill to)</p>
                            <p class="font-bold">{{ $invoice->buyer_name }}</p>
                            @if ($invoice->buyer_address)
                                <p class="whitespace-pre-line">{{ $invoice->buyer_address }}</p>
                            @endif
                            @if ($showGst && $invoice->buyer_gstin)
                                <p class="mt-1">GSTIN/UIN: <span class="font-mono">{{ $invoice->buyer_gstin }}</span></p>
                            @endif
                            @if ($invoice->buyer_state)
                                <p>State Name: {{ $invoice->buyer_state }}</p>
                            @endif
                            @if ($invoice->buyer_email || $invoice->buyer_phone)
                                <p class="text-ink-muted mt-1">{{ collect([$invoice->buyer_email, $invoice->buyer_phone])->filter()->implode(' · ') }}</p>
                            @endif
                        </td>
                    </tr>
                </table>

                {{-- Particulars --}}
                <table class="inv-table inv-table-cards w-full mt-[-1px]">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wide text-ink-muted">
                            <th class="w-8">SI No.</th>
                            <th>Description of Services</th>
                            <th class="w-24">HSN/SAC</th>
                            <th class="w-16 text-right">Qty</th>
                            <th class="w-24 text-right">Rate</th>
                            <th class="w-16 text-right">Tax %</th>
                            <th class="w-28 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->items as $index => $item)
                            <tr>
                                <td class="text-ink-muted" data-label="SI No.">{{ $index + 1 }}</td>
                                <td data-label="Description of Services">{{ $item->item_description }}</td>
                                <td class="font-mono text-ink-muted" data-label="HSN/SAC">{{ $item->hsn_sac ?? '—' }}</td>
                                <td class="text-right font-mono" data-label="Qty">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                                <td class="text-right font-mono" data-label="Rate">&#8377;{{ \App\Support\IndianNumber::format($item->rate) }}</td>
                                <td class="text-right font-mono text-ink-muted" data-label="Tax %">{{ rtrim(rtrim(number_format($item->tax_percent, 2), '0'), '.') }}%</td>
                                <td class="text-right font-mono" data-label="Amount">&#8377;{{ \App\Support\IndianNumber::format($item->amount) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="6" class="text-right font-semibold">Taxable Value</td>
                            <td class="text-right font-mono font-semibold" data-label="Amount">&#8377;{{ \App\Support\IndianNumber::format($invoice->subtotal) }}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- Tax breakdown --}}
                <table class="inv-table inv-table-cards w-full mt-[-1px]">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wide text-ink-muted">
                            <th>Tax Type</th>
                            <th class="w-24 text-right">Rate</th>
                            <th class="w-32 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($invoice->gst_type === 'gst' && $invoice->tax_type === 'igst')
                            <tr>
                                <td>IGST</td>
                                <td class="text-right font-mono" data-label="Rate">{{ rtrim(rtrim(number_format($invoice->igst_rate, 2), '0'), '.') }}%</td>
                                <td class="text-right font-mono" data-label="Amount">&#8377;{{ \App\Support\IndianNumber::format($invoice->igst_amount) }}</td>
                            </tr>
                        @elseif ($invoice->gst_type === 'gst')
                            <tr>
                                <td>CGST</td>
                                <td class="text-right font-mono" data-label="Rate">{{ rtrim(rtrim(number_format($invoice->cgst_rate, 2), '0'), '.') }}%</td>
                                <td class="text-right font-mono" data-label="Amount">&#8377;{{ \App\Support\IndianNumber::format($invoice->cgst_amount) }}</td>
                            </tr>
                            <tr>
                                <td>SGST</td>
                                <td class="text-right font-mono" data-label="Rate">{{ rtrim(rtrim(number_format($invoice->sgst_rate, 2), '0'), '.') }}%</td>
                                <td class="text-right font-mono" data-label="Amount">&#8377;{{ \App\Support\IndianNumber::format($invoice->sgst_amount) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td>Tax</td>
                                <td class="text-right font-mono" data-label="Rate">—</td>
                                <td class="text-right font-mono" data-label="Amount">&#8377;{{ \App\Support\IndianNumber::format($invoice->tax_amount) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="font-semibold">Total</td>
                            <td></td>
                            <td class="text-right font-mono font-bold" data-label="Amount">&#8377;{{ \App\Support\IndianNumber::format($invoice->total_amount) }}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- Amounts in words --}}
                <table class="inv-table w-full mt-[-1px]">
                    <tbody>
                        @if ($invoice->amount_in_words)
                            <tr>
                                <td class="text-ink-muted w-40">Amount Chargeable (in words)</td>
                                <td class="font-medium">{{ $invoice->amount_in_words }}</td>
                            </tr>
                        @endif
                        @if ($invoice->tax_amount_in_words)
                            <tr>
                                <td class="text-ink-muted">Tax Amount (in words)</td>
                                <td class="font-medium">{{ $invoice->tax_amount_in_words }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                {{-- Bank details + signature --}}
                <table class="inv-table w-full mt-[-1px]">
                    <tr>
                        <td class="w-1/2 align-top">
                            @if ($organization->bank_details)
                                <p class="text-ink-muted uppercase text-[11px] tracking-wide mb-1">Company's Bank Details</p>
                                <p class="whitespace-pre-line">{{ $organization->bank_details }}</p>
                            @endif
                        </td>
                        <td class="w-1/2 align-top text-right">
                            <p class="mb-8">for {{ $organization->name }}</p>
                            @if ($organization->signature_path && \App\Models\Setting::get('pdf_show_signature', '1') === '1')
                                <img src="{{ Storage::url($organization->signature_path) }}" class="h-14 object-contain ml-auto">
                            @endif
                            <p class="mt-1">Authorised Signatory</p>
                        </td>
                    </tr>
                </table>

                <table class="inv-table w-full mt-[-1px]">
                    <tr>
                        <td class="text-[11px] text-ink-muted">
                            <p class="font-semibold text-ink mb-1">Declaration</p>
                            <p>We declare that this invoice shows the actual price of the goods/services described and that all particulars are true and correct.</p>
                        </td>
                    </tr>
                </table>

                @php $footerNote = \App\Models\Setting::get('pdf_footer_note', ''); @endphp
                @if ($footerNote)
                    <p class="text-center text-xs text-ink-muted mt-4">{{ $footerNote }}</p>
                @endif

                @if ($organization->support_email)
                    <p class="text-center text-xs text-ink-muted mt-2">
                        For support, contact <span class="font-mono">{{ $organization->support_email }}</span>
                    </p>
                @endif
            </div>

            {{-- Payments (screen only) --}}
                    <div x-data="{ showPaymentForm: {{ $errors->has('paid_on') ? 'true' : 'false' }}, fullyPaid: {{ $invoice->amount_outstanding <= 0 ? 'true' : 'false' }}, showFullyPaidWarning: false }" class="mt-6 bg-surface border border-border rounded-xl p-6 print:hidden">
                    <div class="flex items-center justify-between mb-4">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-wide">Payments</p>
                    @if ($invoice->status !== 'draft')
                        <button type="button"
                                @click="fullyPaid ? (showFullyPaidWarning = true) : (showPaymentForm = !showPaymentForm)"
                                class="text-sm font-medium text-signal hover:text-signal-hover">
                            + Record Payment
                        </button>
                    @endif
                </div>

                <div x-show="showFullyPaidWarning" x-cloak
                     class="mb-4 px-4 py-3 bg-status-overdue/10 border border-status-overdue/20 text-status-overdue text-sm rounded-lg flex items-center justify-between">
                    <span>This invoice is already fully paid. No further payments can be recorded.</span>
                    <button type="button" @click="showFullyPaidWarning = false" class="text-status-overdue/70 hover:text-status-overdue">&times;</button>
                </div>

                <div class="grid grid-cols-3 gap-4 mb-4 pb-4 border-b border-border">
                    <div>
                        <p class="text-xs text-ink-muted">Total</p>
                        <p class="text-base font-semibold text-ink mt-0.5">&#8377;{{ \App\Support\IndianNumber::format($invoice->total_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted">Received</p>
                        <p class="text-base font-semibold text-status-paid mt-0.5">&#8377;{{ \App\Support\IndianNumber::format($invoice->amount_paid) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-ink-muted">Outstanding</p>
                        <p class="text-base font-semibold {{ $invoice->amount_outstanding > 0 ? 'text-status-overdue' : 'text-ink' }} mt-0.5">
                            &#8377;{{ \App\Support\IndianNumber::format($invoice->amount_outstanding) }}
                        </p>
                    </div>
                </div>

                <div x-show="showPaymentForm" x-cloak class="mb-4 pb-4 border-b border-border">
                    <form method="POST" action="{{ route('payments.store', $invoice) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-ink-muted mb-1">Amount</label>
                            <div class="w-full rounded-lg border border-border bg-canvas text-sm font-mono text-ink px-3 py-2">
                                &#8377;{{ \App\Support\IndianNumber::format($invoice->amount_outstanding) }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink-muted mb-1">Date</label>
                            <input type="date" name="paid_on" value="{{ now()->format('Y-m-d') }}" required
                                   class="w-full rounded-lg border-border text-sm focus:border-signal focus:ring-signal">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink-muted mb-1">Method</label>
                            <input type="text" name="method" placeholder="Bank transfer, UPI..."
                                   class="w-full rounded-lg border-border text-sm focus:border-signal focus:ring-signal">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-ink-muted mb-1">Reference</label>
                            <input type="text" name="reference" placeholder="Txn ID"
                                   class="w-full rounded-lg border-border text-sm focus:border-signal focus:ring-signal">
                        </div>
                        <div class="col-span-2 sm:col-span-3">
                            <label class="block text-xs font-medium text-ink-muted mb-1">Notes</label>
                            <input type="text" name="notes"
                                   class="w-full rounded-lg border-border text-sm focus:border-signal focus:ring-signal">
                        </div>
                        <button type="submit" class="px-4 py-2 bg-signal text-white text-sm font-medium rounded-lg hover:bg-signal-hover transition">
                            Save
                        </button>
                    </form>
                </div>

                @forelse ($invoice->payments as $payment)
                    <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-border' : '' }}">
                        <div class="text-sm">
                            <span class="font-mono text-ink">&#8377;{{ \App\Support\IndianNumber::format($payment->amount) }}</span>
                            <span class="text-ink-muted"> · {{ $payment->paid_on->format('d M Y') }}</span>
                            @if ($payment->method)
                                <span class="text-ink-muted"> · {{ $payment->method }}</span>
                            @endif
                            @if ($payment->reference)
                                <span class="text-ink-muted"> · Ref: {{ $payment->reference }}</span>
                            @endif
                            @if ($payment->notes)
                                <p class="text-xs text-ink-muted mt-0.5">{{ $payment->notes }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted">No payments recorded yet.</p>
                @endforelse
            </div>

            {{-- History timeline (screen only) --}}
            @if ($invoice->history->isNotEmpty())
                <div class="mt-6 bg-surface border border-border rounded-xl p-6 print:hidden">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-wide mb-3">History</p>
                    <ul class="space-y-2">
                        @foreach ($invoice->history as $entry)
                            <li class="flex items-center gap-2 text-sm">
                                <span class="w-1.5 h-1.5 rounded-full bg-signal shrink-0"></span>
                                <span class="text-ink capitalize">{{ $entry->action === 'created_random' ? 'created (random invoice number)' : $entry->action }}</span>
                                @if ($entry->performer)
                                    <span class="text-ink-muted">by {{ $entry->performer->name }}</span>
                                @endif
                                <span class="text-ink-muted">· {{ $entry->performed_at->format('d M Y, H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
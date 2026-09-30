<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0F172A; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        .inv-table, .inv-table th, .inv-table td { border: 1px solid #333; }
        .inv-table th, .inv-table td { padding: 5px 7px; }
        .inv-table .plain, .inv-table .plain td { border: none !important; padding: 0 !important; }
        .muted { color: #475569; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .title { text-align: center; font-size: 15px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .uppercase-label { text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px; color: #475569; margin-bottom: 3px; }
        .no-border-top { margin-top: -1px; }
    </style>
</head>
<body>

    <p class="title">{{ $invoice->invoice_type === 'tax_invoice' ? 'Tax Invoice' : 'Proforma Invoice' }}</p>
    @php
        // GSTIN only appears on a GST-charged (tax) invoice. PAN is never
        // shown on the invoice, for either party, in any case.
        $showGst = $invoice->gst_type === 'gst';
    @endphp
    <table class="inv-table">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <table class="plain">
                    <tr>
                        @if ($logoBase64 && \App\Models\Setting::get('pdf_show_logo', '1') === '1')
                            <td style="width:80px; vertical-align:top;">
                                <img src="{{ $logoBase64 }}" style="width:70px; height:70px; object-fit:contain;">
                            </td>
                        @endif
                        <td style="vertical-align:top;">
                            <p class="bold">{{ $organization->name }}</p>
                            <p class="muted">{{ $organization->address }}</p>
                            @if ($showGst && $organization->gst_number)
                                <p>GSTIN/UIN: <span class="mono">{{ $organization->gst_number }}</span></p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width:50%; vertical-align:top;">
                <table class="plain">
                    <tr>
                        <td class="muted" style="padding-bottom:3px;">Invoice No.</td>
                        <td class="muted" style="padding-bottom:3px;">Dated</td>
                    </tr>
                    <tr>
                        <td class="mono bold">{{ $invoice->invoice_number }}</td>
                        <td class="bold">{{ $invoice->invoice_date->format('d-M-y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td style="vertical-align:top;">
                <p class="muted">Reference No. &amp; Date</p>
                <p class="bold">{{ $invoice->reference_number ?? '—' }}</p>
                @if ($invoice->other_references)
                    <p class="muted" style="margin-top:6px;">Other References</p>
                    <p class="bold">{{ $invoice->other_references }}</p>
                @endif
            </td>
            <td style="vertical-align:top;">
                <p class="muted">Buyer's Order No.</p>
                <p class="bold">{{ $invoice->buyer_order_number ?? '—' }}</p>
                @if ($invoice->due_date && $invoice->amount_outstanding > 0)
                    <p class="muted" style="margin-top:6px;">Due Date</p>
                    <p class="bold">{{ $invoice->due_date->format('d-M-y') }}</p>
                @endif
            </td>
        </tr>

        <tr>
            <td colspan="2" style="vertical-align:top;">
                <p class="uppercase-label">Buyer (Bill to)</p>
                <p class="bold">{{ $invoice->buyer_name }}</p>
                @if ($invoice->buyer_address)
                    <p>{{ $invoice->buyer_address }}</p>
                @endif
                @if ($showGst && $invoice->buyer_gstin)
                    <p style="margin-top:4px;">GSTIN/UIN: <span class="mono">{{ $invoice->buyer_gstin }}</span></p>
                @endif
                @if ($invoice->buyer_state)
                    <p>State Name: {{ $invoice->buyer_state }}</p>
                @endif
                @if ($invoice->buyer_email || $invoice->buyer_phone)
                    <p class="muted" style="margin-top:4px;">{{ collect([$invoice->buyer_email, $invoice->buyer_phone])->filter()->implode(' · ') }}</p>
                @endif
            </td>
        </tr>
    </table>

    <table class="inv-table no-border-top">
        <thead>
            <tr class="muted" style="font-size:10px; text-transform:uppercase;">
                <th style="width:28px; text-align:left;">SI No.</th>
                <th style="text-align:left;">Description of Services</th>
                <th style="width:70px; text-align:left;">HSN/SAC</th>
                <th style="width:50px;" class="right">Qty</th>
                <th style="width:70px;" class="right">Rate</th>
                <th style="width:50px;" class="right">Tax %</th>
                <th style="width:85px;" class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $index => $item)
                <tr>
                    <td class="muted">{{ $index + 1 }}</td>
                    <td>{{ $item->item_description }}</td>
                    <td class="mono muted">{{ $item->hsn_sac ?? '—' }}</td>
                    <td class="right mono">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                    <td class="right mono">Rs. {{ \App\Support\IndianNumber::format($item->rate) }}</td>
                    <td class="right mono muted">{{ rtrim(rtrim(number_format($item->tax_percent, 2), '0'), '.') }}%</td>
                    <td class="right mono">Rs. {{ \App\Support\IndianNumber::format($item->amount) }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="6" class="right bold">Taxable Value</td>
                <td class="right mono bold">Rs. {{ \App\Support\IndianNumber::format($invoice->subtotal) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="inv-table no-border-top">
        <thead>
            <tr class="muted" style="font-size:10px; text-transform:uppercase;">
                <th style="text-align:left;">Tax Type</th>
                <th style="width:70px;" class="right">Rate</th>
                <th style="width:100px;" class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @if ($invoice->gst_type === 'gst' && $invoice->tax_type === 'igst')
                <tr>
                    <td>IGST</td>
                    <td class="right mono">{{ rtrim(rtrim(number_format($invoice->igst_rate, 2), '0'), '.') }}%</td>
                    <td class="right mono">Rs. {{ \App\Support\IndianNumber::format($invoice->igst_amount) }}</td>
                </tr>
            @elseif ($invoice->gst_type === 'gst')
                <tr>
                    <td>CGST</td>
                    <td class="right mono">{{ rtrim(rtrim(number_format($invoice->cgst_rate, 2), '0'), '.') }}%</td>
                    <td class="right mono">Rs. {{ \App\Support\IndianNumber::format($invoice->cgst_amount) }}</td>
                </tr>
                <tr>
                    <td>SGST</td>
                    <td class="right mono">{{ rtrim(rtrim(number_format($invoice->sgst_rate, 2), '0'), '.') }}%</td>
                    <td class="right mono">Rs. {{ \App\Support\IndianNumber::format($invoice->sgst_amount) }}</td>
                </tr>
            @else
                <tr>
                    <td>Tax</td>
                    <td class="right mono">—</td>
                    <td class="right mono">Rs. {{ \App\Support\IndianNumber::format($invoice->tax_amount) }}</td>
                </tr>
            @endif
            <tr>
                <td class="bold">Total</td>
                <td></td>
                <td class="right mono bold">Rs. {{ \App\Support\IndianNumber::format($invoice->total_amount) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="inv-table no-border-top">
        @if ($invoice->amount_in_words)
            <tr>
                <td class="muted" style="width:160px;">Amount Chargeable (in words)</td>
                <td class="bold">{{ $invoice->amount_in_words }}</td>
            </tr>
        @endif
        @if ($invoice->tax_amount_in_words)
            <tr>
                <td class="muted">Tax Amount (in words)</td>
                <td class="bold">{{ $invoice->tax_amount_in_words }}</td>
            </tr>
        @endif
    </table>

        <table class="inv-table no-border-top">
        <tr>
            <td style="width:50%; vertical-align:top;">
                @if ($organization->bank_details)
                    <p class="uppercase-label">Company's Bank Details</p>
                    <p>{{ $organization->bank_details }}</p>
                @endif
            </td>
            <td style="width:50%; vertical-align:top; text-align:right;">
                <p style="margin-bottom:30px;">for {{ $organization->name }}</p>
                @if ($signatureBase64 && \App\Models\Setting::get('pdf_show_signature', '1') === '1')
                    <img src="{{ $signatureBase64 }}" style="height:50px; object-fit:contain;">
                @endif
                <p style="margin-top:4px;">Authorised Signatory</p>
            </td>
        </tr>
    </table>

    <table class="inv-table no-border-top">
        <tr>
            <td style="font-size:10px; color:#475569;">
                <p class="bold" style="color:#0F172A; margin-bottom:3px;">Declaration</p>
                <p>We declare that this invoice shows the actual price of the goods/services described and that all particulars are true and correct.</p>
            </td>
        </tr>
    </table>

    @php $footerNote = \App\Models\Setting::get('pdf_footer_note', ''); @endphp
    @if ($footerNote)
        <p style="text-align:center; font-size:10px; color:#0F172A; margin-top:10px;">{{ $footerNote }}</p>
    @endif

    @if ($organization->support_email)
        <p style="text-align:center; font-size:10px; color:#475569; margin-top:6px;">
            For support, contact <span class="mono">{{ $organization->support_email }}</span>
        </p>
    @endif

    <p style="text-align:center; font-size:9px; color:#475569; margin-top:10px;">
        This is a computer generated invoice and does not require a physical signature.
    </p>

</body>
</html>
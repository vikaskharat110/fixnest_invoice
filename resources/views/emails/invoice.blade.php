<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; color: #0F172A; line-height: 1.6; font-size: 14px; }
        .container { max-width: 480px; margin: 0 auto; padding: 24px; }
        .muted { color: #475569; font-size: 13px; }
        .amount { font-family: 'Courier New', monospace; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <p>Dear {{ $invoice->buyer_name }},</p>

        <p>
            Please find attached invoice <strong>{{ $invoice->invoice_number }}</strong>
            from {{ $organization->name }}, dated {{ $invoice->invoice_date->format('d M Y') }}.
        </p>

        <p>
            Amount due: <span class="amount">Rs. {{ number_format($invoice->total_amount, 2) }}</span>
            @if ($invoice->due_date)
                <br><span class="muted">Due by {{ $invoice->due_date->format('d M Y') }}</span>
            @endif
        </p>

        <p>If you have any questions about this invoice, please reach out to us directly.</p>

        <p class="muted" style="margin-top: 32px;">
            This is an automated email from {{ $organization->name }} via Fixnest.
        </p>
    </div>
</body>
</html>
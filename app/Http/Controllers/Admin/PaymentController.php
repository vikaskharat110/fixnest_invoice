<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status === 'draft') {
            return back()->with('error', 'Cannot record a payment against a draft invoice.');
        }

        // Refresh payments relation so we're checking against the latest total,
        // not a stale value loaded earlier in the request lifecycle.
        $invoice->load('payments');

        $outstanding = round((float) $invoice->total_amount - (float) $invoice->payments->sum('amount'), 2);

        if ($outstanding <= 0) {
            return back()->with('error', 'This invoice is already fully paid. No further payments can be recorded.');
        }

        $validated = $request->validate([
            'paid_on' => ['required', 'date'],
            'method' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Amount is never taken from the request — it always equals the
        // invoice's current outstanding balance, recorded in one shot.
        $invoice->payments()->create([
            ...$validated,
            'amount' => $outstanding,
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Payment of ₹' . number_format($outstanding, 2) . ' recorded. Invoice fully paid.');
    }
}
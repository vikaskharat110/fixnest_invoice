<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\StoreNextInvoiceRequest;
use App\Http\Requests\StoreRandomInvoiceRequest;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceHistory;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Services\InvoiceNumberGenerator;
use App\Services\InvoiceTotalsCalculator;
use App\Services\RandomInvoiceGenerator;
use App\Services\SalesOrderNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\EmailLog;
use App\Jobs\SendInvoiceEmailJob;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $companies = Company::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $invoices = Invoice::query()
            ->with('company:id,name')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->integer('company_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->trim()->toString();
                $q->where(function ($qq) use ($search) {
                    $qq->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('buyer_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'companies'));
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load([
            'company',
            'order',
            'items' => fn ($q) => $q->orderBy('sort_order'),
            'history' => fn ($q) => $q->orderBy('performed_at'),
            'history.performer:id,name',
        ]);

        $organization = Organization::current();

        return view('invoices.show', compact('invoice', 'organization'));
    }

    public function create(Request $request): View
    {
        $companies = $this->companiesForWizard();
        return view('invoices.create', [
            'companies' => $companies,
            'invoice' => null,
            'preselectedOrderId' => $request->integer('order_id') ?: null,
        ]);
    }

    /**
     * "Random Invoice" — a separate entry point that reuses the standard wizard
     * but issues an admin-entered (or auto-generated) invoice number instead of the next sequential one.
     */
    public function createRandom(RandomInvoiceGenerator $randomGenerator): View
    {
        return view('invoices.create', [
            'companies' => $this->companiesForWizard(),
            'invoice' => null,
            'preselectedOrderId' => null,
            'randomNumberPrefix' => $randomGenerator->prefixFor(Organization::current()),
        ]);
    }

    public function edit(Invoice $invoice): View|RedirectResponse
    {
        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only draft invoices can be edited.');
        }
        $invoice->load(['items' => fn ($q) => $q->orderBy('sort_order'), 'order']);
        // Keep the currently-linked order selectable even if it's since become
        // fully billed by other invoices, so this edit doesn't lose its order.
        $companies = $this->companiesForWizard($invoice->order_id);
        return view('invoices.create', compact('companies', 'invoice') + ['preselectedOrderId' => null]);
    }

    /**
     * "Generate Next Invoice" — the lightweight follow-up flow for an order that
     * already has at least one invoice. Only asks for an amount; everything else
     * (buyer snapshot, item structure, tax type) is cloned from the order's most
     * recent invoice and recalculated through the same InvoiceTotalsCalculator
     * the full wizard uses.
     */
    public function createNext(SalesOrder $order): View|RedirectResponse
    {
        $sourceInvoice = $order->invoices()->latest('id')->first();

        if (! $sourceInvoice) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Create the first invoice for this order using the standard invoice form.');
        }

        if ($order->status !== 'open' || $order->amount_remaining <= 0) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'This order has no remaining amount left to bill.');
        }

        // Drafts don't count towards amount_billed, so an unresolved one is easy
        // to lose track of — surface it here so the user checks/finalizes it
        // before adding yet another invoice on top.
        $draftInvoices = $order->invoices()->where('status', 'draft')->orderByDesc('id')->get();

        return view('orders.generate-invoice', compact('order', 'draftInvoices'));
    }

    public function storeNext(
        StoreNextInvoiceRequest $request,
        SalesOrder $order,
        InvoiceNumberGenerator $numberGenerator,
        InvoiceTotalsCalculator $totalsCalculator
    ): RedirectResponse {
        if ($order->status !== 'open' || $order->amount_remaining <= 0) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'This order has no remaining amount left to bill.');
        }

        $sourceInvoice = $order->invoices()->latest('id')->first();

        if (! $sourceInvoice) {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Create the first invoice for this order using the standard invoice form.');
        }

        $sourceInvoice->load('items');
        $requestedAmount = (float) $request->validated('amount');
        $sourceTotal = (float) $sourceInvoice->total_amount;
        $scaleFactor = $sourceTotal > 0 ? $requestedAmount / $sourceTotal : 0;

        $items = $sourceInvoice->items->sortBy('sort_order')->values()->map(fn ($item) => [
            'item_description' => $item->item_description,
            'hsn_sac' => $item->hsn_sac,
            'quantity' => (float) $item->quantity,
            'rate' => round((float) $item->rate * $scaleFactor, 2),
            'tax_percent' => (float) $item->tax_percent,
        ])->all();

        $taxType = $sourceInvoice->gst_type === 'gst' ? ($sourceInvoice->tax_type ?? 'cgst_sgst') : 'cgst_sgst';
        $totals = $totalsCalculator->calculate($items, $taxType);

        // Scaling each item's rate independently and rounding at every step
        // (rate -> amount -> tax) can leave the invoice a paisa or two off the
        // amount the user actually asked for (and that was validated against
        // the order's remaining capacity). Nudge the last line item's rate to
        // absorb that rounding remainder so the stored total is exact.
        $diff = round($requestedAmount - (float) $totals['total_amount'], 2);
        if ($diff !== 0.0) {
            $lastIndex = array_key_last($items);
            $qty = (float) $items[$lastIndex]['quantity'];
            $taxPercent = (float) $items[$lastIndex]['tax_percent'];
            $divisor = $qty * (1 + $taxPercent / 100);

            if ($qty > 0 && $divisor > 0) {
                $items[$lastIndex]['rate'] = round($items[$lastIndex]['rate'] + ($diff / $divisor), 2);
                $totals = $totalsCalculator->calculate($items, $taxType);
            }
        }

        // Re-check against live order figures at write time (mirrors finalize()'s
        // belt-and-braces re-check) — the FormRequest already validated against
        // amount_remaining, but this reuses the single shared capacity guard.
        $this->assertWithinOrderCapacity(
            orderTotalValue: (float) $order->total_value,
            alreadyBilled: (float) $order->amount_billed,
            thisInvoiceTotal: (float) $totals['total_amount'],
            errorKey: 'amount',
        );

        $invoice = DB::transaction(function () use ($order, $sourceInvoice, $numberGenerator, $totals, $taxType) {
            $company = $order->company;
            $organization = Organization::current();

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'order_id' => $order->id,
                'buyer_name' => $company->name,
                'buyer_email' => $company->email,
                'buyer_phone' => $company->phone,
                'buyer_address' => $company->address,
                'buyer_gstin' => $company->gst_number,
                'buyer_state' => $company->state,
                'template' => $sourceInvoice->template,
                'created_by' => auth()->id(),
                'invoice_number' => $numberGenerator->generateFor($organization),
                'invoice_type' => $sourceInvoice->invoice_type,
                'gst_type' => $sourceInvoice->gst_type,
                'tax_type' => $taxType,
                'status' => 'draft',
                'invoice_date' => now()->toDateString(),
                'due_date' => null,
                'reference_number' => $sourceInvoice->reference_number,
                'other_references' => $sourceInvoice->other_references,
                'delivery_note' => $sourceInvoice->delivery_note,
                'buyer_order_number' => $sourceInvoice->buyer_order_number,
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'cgst_rate' => $totals['cgst_rate'],
                'cgst_amount' => $totals['cgst_amount'],
                'sgst_rate' => $totals['sgst_rate'],
                'sgst_amount' => $totals['sgst_amount'],
                'igst_rate' => $totals['igst_rate'],
                'igst_amount' => $totals['igst_amount'],
                'total_amount' => $totals['total_amount'],
                'amount_in_words' => $totals['amount_in_words'],
                'tax_amount_in_words' => $totals['tax_amount_in_words'],
            ]);

            foreach ($totals['items'] as $index => $item) {
                $invoice->items()->create([
                    ...$item,
                    'sort_order' => $index,
                ]);
            }

            InvoiceHistory::create([
                'invoice_id' => $invoice->id,
                'action' => 'created',
                'performed_by' => auth()->id(),
                'performed_at' => now(),
            ]);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('status', "Invoice {$invoice->invoice_number} created as draft.");
    }

    protected function companiesForWizard(?int $keepOrderId = null)
    {
        return Company::where('is_active', true)
            ->with(['salesOrders' => fn ($q) => $q->where('status', 'open')->orderByDesc('id')])
            ->orderBy('name')
            ->get(['id', 'name', 'gst_number', 'pan_number', 'address', 'email', 'phone', 'state'])
            ->map(function ($company) use ($keepOrderId) {
                // Drop sales orders with zero remaining capacity from the wizard —
                // there's nothing left to bill against them. Exception: always keep
                // $keepOrderId (the order an invoice being edited is already linked
                // to), even if it's now fully billed, so editing doesn't lose it.
                $company->setRelation(
                    'salesOrders',
                    $company->salesOrders->filter(
                        fn ($order) => $order->amount_remaining > 0 || $order->id === $keepOrderId
                    )->values()
                );

                return $company;
            });
    }

    /**
     * Throws a ValidationException (redirects back with the error attached to the
     * given field) if the given invoice total would push the order past its total_value.
     * All figures passed in must be tax-inclusive — there's only one definition of
     * "capacity" in this app now, and it's the tax-inclusive total.
     *
     * $alreadyBilled should be the order's committed total EXCLUDING the invoice
     * currently being created/edited (drafts never count towards this).
     */
    protected function assertWithinOrderCapacity(float $orderTotalValue, float $alreadyBilled, float $thisInvoiceTotal, string $errorKey): void
    {
        $available = $orderTotalValue - $alreadyBilled;

        // Small epsilon to tolerate decimal rounding noise, not to allow real overage.
        if ($thisInvoiceTotal > $available + 0.01) {
            $availableDisplay = number_format(max(0, $available), 2);
            $totalDisplay = number_format($thisInvoiceTotal, 2);

            throw ValidationException::withMessages([
                $errorKey => "This invoice's total (₹{$totalDisplay}) exceeds the sales order's remaining value of ₹{$availableDisplay}. Reduce the line items, or link a different / larger order.",
            ]);
        }
    }

    public function store(
        StoreInvoiceRequest $request,
        InvoiceNumberGenerator $numberGenerator,
        InvoiceTotalsCalculator $totalsCalculator,
        SalesOrderNumberGenerator $orderNumberGenerator
    ): RedirectResponse {
        return $this->storeInvoice($request, $numberGenerator, $totalsCalculator, $orderNumberGenerator);
    }

    /**
     * Random-number variant of store(): identical validation, totals, GST,
     * order-capacity and draft rules — only the invoice number source and the
     * history action differ. Never touches the sequential counter.
     */
    public function storeRandom(
        StoreRandomInvoiceRequest $request,
        InvoiceNumberGenerator $numberGenerator,
        InvoiceTotalsCalculator $totalsCalculator,
        SalesOrderNumberGenerator $orderNumberGenerator,
        RandomInvoiceGenerator $randomGenerator
    ): RedirectResponse {
        try {
            return $this->storeInvoice($request, $numberGenerator, $totalsCalculator, $orderNumberGenerator, $randomGenerator);
        } catch (QueryException $e) {
            // Never show raw SQL to the user. The transaction has already rolled back,
            // so nothing was created; log the real error and send them back to the form.
            report($e);

            // Unique-key violation: someone else took this number a split second ago.
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return back()->withInput()->withErrors([
                    'random_suffix' => 'That invoice number was just taken. Please enter a different number or leave it blank.',
                ]);
            }

            return back()->withInput()->with('error', 'We could not create the random invoice right now. Please try again in a moment, and contact support if the problem continues.');
        }
    }

    protected function storeInvoice(
        StoreInvoiceRequest $request,
        InvoiceNumberGenerator $numberGenerator,
        InvoiceTotalsCalculator $totalsCalculator,
        SalesOrderNumberGenerator $orderNumberGenerator,
        ?RandomInvoiceGenerator $randomGenerator = null
    ): RedirectResponse {

        $data = $request->validated();

        if ($data['invoice_type'] === 'invoice') {
            $data['gst_type'] = 'non_gst';
        }

        // Tax is always zero without GST — whether the invoice is Unpaid (which
        // is always non-GST) or a Paid/Tax Invoice explicitly billed non-GST.
        // Enforced here regardless of what the client sent.
        if ($data['gst_type'] === 'non_gst') {
            $data['tax_type'] = null;
            $data['new_order_tax_percent'] = 0;
            foreach ($data['items'] as &$item) {
                $item['tax_percent'] = 0;
            }
            unset($item);
        }

        $taxType = $data['gst_type'] === 'gst' ? ($data['tax_type'] ?? 'cgst_sgst') : 'cgst_sgst';
        $totals = $totalsCalculator->calculate($data['items'], $taxType);

        // --- Order-capacity check happens BEFORE we touch the DB at all ---
        $newOrderTotalValue = null;

        if ($data['order_mode'] === 'existing') {
            $existingOrder = SalesOrder::where('company_id', $data['company_id'])->findOrFail($data['order_id']);
            $this->assertWithinOrderCapacity(
                orderTotalValue: (float) $existingOrder->total_value,
                alreadyBilled: (float) $existingOrder->amount_billed, // sum of non-draft invoices already on this order
                thisInvoiceTotal: (float) $totals['total_amount'],
                errorKey: 'order_id',
            );
        } else {
            // New order's total is tax-inclusive: base_value + GST computed here,
            // not trusted from any client-supplied total.
            $newOrderBaseValue = (float) $data['new_order_base_value'];
            $newOrderTaxPercent = (float) $data['new_order_tax_percent'];
            $newOrderTotalValue = round($newOrderBaseValue + ($newOrderBaseValue * $newOrderTaxPercent / 100), 2);

            $this->assertWithinOrderCapacity(
                orderTotalValue: $newOrderTotalValue,
                alreadyBilled: 0.0, // brand-new order, nothing billed against it yet
                thisInvoiceTotal: (float) $totals['total_amount'],
                errorKey: 'new_order_base_value',
            );
        }

        $invoice = DB::transaction(function () use ($data, $numberGenerator, $orderNumberGenerator, $randomGenerator, $totals, $taxType, $newOrderTotalValue) {
            $company = Company::findOrFail($data['company_id']);
            $organization = Organization::current();

            if ($data['order_mode'] === 'existing') {
                $order = SalesOrder::where('company_id', $company->id)->findOrFail($data['order_id']);
            } else {
                $order = SalesOrder::create([
                    'company_id' => $company->id,
                    'order_number' => $orderNumberGenerator->generateFor($organization),
                    'base_value' => $data['new_order_base_value'],
                    'tax_percent' => $data['new_order_tax_percent'],
                    'total_value' => $newOrderTotalValue,
                    'description' => $data['new_order_description'] ?? null,
                    'order_date' => $data['invoice_date'],
                    'status' => 'open',
                ]);
            }

            $invoiceNumber = $randomGenerator
                ? $randomGenerator->generateFor($organization, $data['random_suffix'] ?? null)
                : $numberGenerator->generateFor($organization);

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'order_id' => $order->id,
                'buyer_name' => $company->name,
                'buyer_email' => $company->email,
                'buyer_phone' => $company->phone,
                'buyer_address' => $company->address,
                'buyer_gstin' => $company->gst_number,
                'buyer_state' => $company->state,
                'template' => $data['template'] ?? 'classic',
                'created_by' => auth()->id(),
                'invoice_number' => $invoiceNumber,
                'number_source' => $randomGenerator ? 'random' : 'sequential',
                'invoice_type' => $data['invoice_type'],
                'gst_type' => $data['gst_type'],
                'tax_type' => $taxType,
                'status' => 'draft',
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'other_references' => $data['other_references'] ?? null,
                'delivery_note' => $data['delivery_note'] ?? null,
                'buyer_order_number' => $data['buyer_order_number'] ?? null,
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'cgst_rate' => $totals['cgst_rate'],
                'cgst_amount' => $totals['cgst_amount'],
                'sgst_rate' => $totals['sgst_rate'],
                'sgst_amount' => $totals['sgst_amount'],
                'igst_rate' => $totals['igst_rate'],
                'igst_amount' => $totals['igst_amount'],
                'total_amount' => $totals['total_amount'],
                'amount_in_words' => $totals['amount_in_words'],
                'tax_amount_in_words' => $totals['tax_amount_in_words'],
            ]);

            foreach ($totals['items'] as $index => $item) {
                $invoice->items()->create([
                    ...$item,
                    'sort_order' => $index,
                ]);
            }

            InvoiceHistory::create([
                'invoice_id' => $invoice->id,
                'action' => $randomGenerator ? 'created_random' : 'created',
                'performed_by' => auth()->id(),
                'performed_at' => now(),
            ]);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('status', "Invoice {$invoice->invoice_number} created as draft.");
    }

    public function update(
        StoreInvoiceRequest $request,
        Invoice $invoice,
        InvoiceTotalsCalculator $totalsCalculator
    ): RedirectResponse {
        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $data = $request->validated();

        if ($data['invoice_type'] === 'invoice') {
            $data['gst_type'] = 'non_gst';
        }

        // Tax is always zero without GST — whether the invoice is Unpaid (which
        // is always non-GST) or a Paid/Tax Invoice explicitly billed non-GST.
        // Enforced here regardless of what the client sent.
        if ($data['gst_type'] === 'non_gst') {
            $data['tax_type'] = null;
            foreach ($data['items'] as &$item) {
                $item['tax_percent'] = 0;
            }
            unset($item);
        }

        $taxType = $data['gst_type'] === 'gst' ? ($data['tax_type'] ?? 'cgst_sgst') : 'cgst_sgst';
        $totals = $totalsCalculator->calculate($data['items'], $taxType);

        // Order is locked on edit, so re-check this draft's new total against it.
        // The invoice being edited is still a draft, so it's already excluded from
        // amount_billed (that accessor only counts non-draft invoices) — no need
        // to subtract anything out.
        if ($invoice->order) {
            $this->assertWithinOrderCapacity(
                orderTotalValue: (float) $invoice->order->total_value,
                alreadyBilled: (float) $invoice->order->amount_billed,
                thisInvoiceTotal: (float) $totals['total_amount'],
                errorKey: 'items', // surfaces on Step 5, where the amount actually changed
            );
        }

        DB::transaction(function () use ($data, $invoice, $totals, $taxType) {
            // company_id and order_id are locked — not touched here, regardless of what's in the payload.
            // Buyer snapshot is refreshed from the (locked) company in case its details changed since creation.
            $company = $invoice->company;

            $invoice->update([
                'buyer_name' => $company->name,
                'buyer_email' => $company->email,
                'buyer_phone' => $company->phone,
                'buyer_address' => $company->address,
                'buyer_gstin' => $company->gst_number,
                'buyer_state' => $company->state,
                'invoice_type' => $data['invoice_type'],
                'template' => $data['template'] ?? 'classic',
                'gst_type' => $data['gst_type'],
                'tax_type' => $taxType,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'other_references' => $data['other_references'] ?? null,
                'delivery_note' => $data['delivery_note'] ?? null,
                'buyer_order_number' => $data['buyer_order_number'] ?? null,
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'cgst_rate' => $totals['cgst_rate'],
                'cgst_amount' => $totals['cgst_amount'],
                'sgst_rate' => $totals['sgst_rate'],
                'sgst_amount' => $totals['sgst_amount'],
                'igst_rate' => $totals['igst_rate'],
                'igst_amount' => $totals['igst_amount'],
                'total_amount' => $totals['total_amount'],
                'amount_in_words' => $totals['amount_in_words'],
                'tax_amount_in_words' => $totals['tax_amount_in_words'],
            ]);

            $invoice->items()->delete();
            foreach ($totals['items'] as $index => $item) {
                $invoice->items()->create([
                    ...$item,
                    'sort_order' => $index,
                ]);
            }

            InvoiceHistory::create([
                'invoice_id' => $invoice->id,
                'action' => 'updated',
                'performed_by' => auth()->id(),
                'performed_at' => now(),
            ]);
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('status', "Invoice {$invoice->invoice_number} updated.");
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        // Only drafts are deletable — a finalized/sent/archived invoice is a
        // committed record and must stay, same rule as edit()/finalize().
        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only draft invoices can be deleted.');
        }

        $order = $invoice->order;
        $invoiceNumber = $invoice->invoice_number;
        $invoice->delete();

        if ($order) {
            return redirect()->route('orders.show', $order)
                ->with('status', "Draft invoice {$invoiceNumber} deleted.");
        }

        return redirect()->route('invoices.history')
            ->with('status', "Draft invoice {$invoiceNumber} deleted.");
    }

    public function finalize(Invoice $invoice): RedirectResponse
    {
        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only draft invoices can be finalized.');
        }

        // Real gatekeeper: only finalized/sent invoices count towards amount_billed,
        // so this is the point where an over-limit invoice actually commits against
        // the order. Catches cases where multiple drafts were created against the
        // same order and would collectively exceed it. Uses the same shared helper
        // as store()/update() so there's only one definition of "over capacity"
        // anywhere in the app.
        if ($invoice->order) {
            try {
                $this->assertWithinOrderCapacity(
                    orderTotalValue: (float) $invoice->order->total_value,
                    alreadyBilled: (float) $invoice->order->amount_billed,
                    thisInvoiceTotal: (float) $invoice->total_amount,
                    errorKey: 'finalize',
                );
            } catch (ValidationException $e) {
                $message = $e->validator->errors()->first('finalize');

                return redirect()->route('invoices.show', $invoice)
                    ->with('error', "Cannot finalize — {$message} Edit the invoice's line items, or update the order's total value first.");
            }
        }

        $invoice->update(['status' => 'finalized']);

        InvoiceHistory::create([
            'invoice_id' => $invoice->id,
            'action' => 'finalized',
            'performed_by' => auth()->id(),
            'performed_at' => now(),
        ]);

        return redirect()->route('invoices.show', $invoice)
            ->with('status', "Invoice {$invoice->invoice_number} finalized and locked.");
    }

    public function sendEmail(Request $request, Invoice $invoice): RedirectResponse
    {
        if (! in_array($invoice->status, ['finalized', 'sent'], true)) {
            return redirect()->route('invoices.show', $invoice)
                ->with('error', 'Only finalized invoices can be emailed.');
        }

        $validated = $request->validate([
            'sent_to' => ['required', 'email'],
            'cc' => ['nullable', 'string'],
            'bcc' => ['nullable', 'string'],
            'subject' => ['nullable', 'string', 'max:255'],
        ]);

        $emailLog = EmailLog::create([
            'invoice_id' => $invoice->id,
            'sent_to' => $validated['sent_to'],
            'cc' => $validated['cc'] ?? null,
            'bcc' => $validated['bcc'] ?? null,
            'subject' => ($validated['subject'] ?? null) ?: "Invoice {$invoice->invoice_number} from " . Organization::current()->name,
            'status' => 'pending',
        ]);

        SendInvoiceEmailJob::dispatch($emailLog->id);

        return redirect()->route('invoices.show', $invoice)
            ->with('status', "Email queued for {$validated['sent_to']}. It will be sent shortly.");
    }

    public function downloadPdf(Invoice $invoice): \Illuminate\Http\Response
    {
        $invoice->load(['company', 'items']);
        $organization = Organization::current();

        $logoBase64 = null;
        if ($organization->logo_path && Storage::disk('public')->exists($organization->logo_path)) {
            $ext = pathinfo($organization->logo_path, PATHINFO_EXTENSION);
            $logoBase64 = "data:image/{$ext};base64," . base64_encode(Storage::disk('public')->get($organization->logo_path));
        }

        $signatureBase64 = null;
        if ($organization->signature_path && Storage::disk('public')->exists($organization->signature_path)) {
            $ext = pathinfo($organization->signature_path, PATHINFO_EXTENSION);
            $signatureBase64 = "data:image/{$ext};base64," . base64_encode(Storage::disk('public')->get($organization->signature_path));
        }

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'logoBase64', 'signatureBase64', 'organization'))
            ->setPaper('a4');

        $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number);

        return $pdf->stream("{$safeFilename}.pdf");
    }
}
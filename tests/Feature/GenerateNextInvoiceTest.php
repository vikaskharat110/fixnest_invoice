<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GenerateNextInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($user);

        return $user;
    }

    protected function makeOrderWithFirstInvoice(float $orderTotal, float $firstInvoiceTotal): SalesOrder
    {
        $company = Company::create([
            'name' => 'Acme Test Pvt Ltd',
            'gst_number' => '27ABCDE1234F1Z5',
            'address' => 'Test Address',
            'email' => 'acme@example.com',
            'phone' => '9999999999',
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);

        $order = SalesOrder::create([
            'company_id' => $company->id,
            'order_number' => 'FIX-SO/2026/TEST',
            'base_value' => round($orderTotal / 1.18, 2),
            'tax_percent' => 18,
            'total_value' => $orderTotal,
            'order_date' => now(),
            'status' => 'open',
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'order_id' => $order->id,
            'buyer_name' => $company->name,
            'buyer_email' => $company->email,
            'buyer_phone' => $company->phone,
            'buyer_address' => $company->address,
            'buyer_gstin' => $company->gst_number,
            'buyer_state' => $company->state,
            'invoice_number' => 'INV/2026/0001',
            'invoice_type' => 'tax_invoice',
            'gst_type' => 'gst',
            'tax_type' => 'cgst_sgst',
            'status' => 'finalized',
            'invoice_date' => now(),
            'subtotal' => round($firstInvoiceTotal / 1.18, 2),
            'tax_amount' => round($firstInvoiceTotal - $firstInvoiceTotal / 1.18, 2),
            'cgst_rate' => 9,
            'cgst_amount' => round(($firstInvoiceTotal - $firstInvoiceTotal / 1.18) / 2, 2),
            'sgst_rate' => 9,
            'sgst_amount' => round(($firstInvoiceTotal - $firstInvoiceTotal / 1.18) / 2, 2),
            'igst_rate' => 0,
            'igst_amount' => 0,
            'total_amount' => $firstInvoiceTotal,
            'amount_in_words' => 'TEST',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_description' => 'Consulting services',
            'hsn_sac' => '9983',
            'quantity' => 1,
            'rate' => round($firstInvoiceTotal / 1.18, 2),
            'amount' => round($firstInvoiceTotal / 1.18, 2),
            'tax_percent' => 18,
            'tax_amount' => round($firstInvoiceTotal - $firstInvoiceTotal / 1.18, 2),
            'sort_order' => 0,
        ]);

        return $order->fresh();
    }

    public function test_generate_next_invoice_screen_shows_order_figures(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrderWithFirstInvoice(118000.00, 59000.00);

        $response = $this->get(route('orders.generate-invoice.create', $order));

        $response->assertOk();
        $response->assertSee('Generate Next Invoice');
        $response->assertSee('59,000.00'); // remaining
    }

    public function test_it_clones_the_source_invoice_with_a_new_number_and_recalculated_totals(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrderWithFirstInvoice(118000.00, 59000.00);
        $sourceInvoice = $order->invoices()->first();

        $response = $this->post(route('orders.generate-invoice.store', $order), [
            'amount' => 29500.00,
        ]);

        $newInvoice = Invoice::latest('id')->first();

        $response->assertRedirect(route('invoices.show', $newInvoice));
        $this->assertNotSame($sourceInvoice->id, $newInvoice->id);
        $this->assertSame($order->id, $newInvoice->order_id);
        $this->assertNotSame($sourceInvoice->invoice_number, $newInvoice->invoice_number);
        $this->assertSame('draft', $newInvoice->status);
        $this->assertEqualsWithDelta(29500.00, (float) $newInvoice->total_amount, 0.05);

        // Original invoice untouched.
        $sourceInvoice->refresh();
        $this->assertSame('finalized', $sourceInvoice->status);
        $this->assertEqualsWithDelta(59000.00, (float) $sourceInvoice->total_amount, 0.01);

        // Order's billed/remaining now reflects both invoices.
        $order->refresh();
        $this->assertEqualsWithDelta(88500.00, $order->amount_billed, 0.05);
        $this->assertEqualsWithDelta(29500.00, $order->amount_remaining, 0.05);
    }

    public function test_it_rejects_an_amount_exceeding_the_remaining_balance(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrderWithFirstInvoice(118000.00, 59000.00);

        // Remaining is 59,000 — try to bill 60,000 via direct request (bypassing UI).
        $response = $this->post(route('orders.generate-invoice.store', $order), [
            'amount' => 60000.00,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertSame(1, $order->invoices()->count());
    }

    public function test_it_rejects_a_zero_or_negative_amount(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrderWithFirstInvoice(118000.00, 59000.00);

        $response = $this->post(route('orders.generate-invoice.store', $order), [
            'amount' => 0,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertSame(1, $order->invoices()->count());
    }

    public function test_it_blocks_further_invoices_once_the_order_is_fully_billed(): void
    {
        $this->actingAsAdmin();
        $order = $this->makeOrderWithFirstInvoice(59000.00, 59000.00);

        // Fully billed already — even the GET screen should refuse.
        $response = $this->get(route('orders.generate-invoice.create', $order));
        $response->assertRedirect(route('orders.show', $order));

        // Direct POST bypassing the UI must also be rejected server-side.
        $response = $this->post(route('orders.generate-invoice.store', $order), [
            'amount' => 1.00,
        ]);
        $response->assertSessionHasErrors('amount');
        $this->assertSame(1, $order->invoices()->count());
    }
}

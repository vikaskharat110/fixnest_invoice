<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceHistory;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RandomInvoiceTest extends TestCase
{
    use RefreshDatabase;

    /** Not via the factory: it sets phone columns that no migration creates. */
    protected function makeUser(): User
    {
        return User::forceCreate([
            'name' => 'Test User',
            'email' => uniqid('u', true) . '@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    }

    protected function actingAsAdmin(): User
    {
        $user = $this->makeUser();
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($user);

        return $user;
    }

    protected function makeCompany(): Company
    {
        return Company::create([
            'name' => 'Acme Test Pvt Ltd',
            'gst_number' => '27ABCDE1234F1Z5',
            'address' => 'Test Address',
            'email' => 'acme@example.com',
            'phone' => '9999999999',
            'state' => 'Maharashtra',
            'is_active' => true,
        ]);
    }

    /** Payload for a brand-new order; 1 item of $rate + 18% GST => total = $rate * 1.18. */
    protected function payload(Company $company, array $overrides = []): array
    {
        return array_merge([
            'company_id' => $company->id,
            'order_mode' => 'new',
            'new_order_base_value' => 100000,
            'new_order_tax_percent' => 18,
            'invoice_type' => 'tax_invoice',
            'gst_type' => 'gst',
            'tax_type' => 'cgst_sgst',
            'invoice_date' => now()->toDateString(),
            'template' => 'classic',
            'items' => [[
                'item_description' => 'Consulting',
                'hsn_sac' => '9983',
                'quantity' => 1,
                'rate' => 100000,
                'tax_percent' => 18,
            ]],
        ], $overrides);
    }

    protected function normalInvoice(Company $company, array $overrides = []): Invoice
    {
        $this->post(route('invoices.store'), $this->payload($company, $overrides))->assertSessionHasNoErrors();

        return Invoice::latest('id')->first();
    }

    protected function randomInvoice(Company $company, array $overrides = [])
    {
        return $this->post(route('invoices.random.store'), $this->payload($company, $overrides));
    }

    protected function seq(Invoice $invoice): int
    {
        return (int) substr($invoice->invoice_number, strrpos($invoice->invoice_number, '/') + 1);
    }

    // ---- Feature 1: starting number -------------------------------------------------

    public function test_default_sequence_starts_at_one(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();

        $this->assertSame(1, $this->seq($this->normalInvoice($company)));
        $this->assertSame(2, $this->seq($this->normalInvoice($company)));
        $this->assertSame(3, $this->seq($this->normalInvoice($company)));
    }

    #[DataProvider('resetModes')]
    public function test_configured_start_number_seeds_first_invoice_in_every_reset_mode(string $mode): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();

        $this->post(route('settings.update'), [
            'app_name' => 'Fixnest',
            'invoice_numbering_reset' => $mode,
            'invoice_start_number' => 20,
        ])->assertSessionHasNoErrors();

        $first = $this->normalInvoice($company);
        $this->assertMatchesRegularExpression('#^INV/\d{4}(-\d{2})?/0020$#', $first->invoice_number);
        $this->assertSame(21, $this->seq($this->normalInvoice($company)));
        $this->assertSame(22, $this->seq($this->normalInvoice($company)));
    }

    public static function resetModes(): array
    {
        return [['financial_year'], ['calendar_year'], ['never']];
    }

    public function test_settings_page_shows_editable_then_locked_start_number(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();

        $this->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Starting Invoice Number')
            ->assertSee('Default is 1.')
            ->assertDontSee('Locked — invoice numbering has already started');

        $this->normalInvoice($company);

        $this->get(route('settings.edit'))->assertSee('Locked — invoice numbering has already started', false);
    }

    public function test_saving_settings_does_not_touch_the_counter(): void
    {
        $this->actingAsAdmin();
        $this->post(route('settings.update'), [
            'app_name' => 'Fixnest',
            'invoice_numbering_reset' => 'financial_year',
            'invoice_start_number' => 20,
        ]);

        $this->assertSame(1, (int) Organization::current()->next_invoice_number);
    }

    public function test_start_number_is_locked_once_numbering_has_started(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();
        $this->normalInvoice($company); // 0001

        $response = $this->post(route('settings.update'), [
            'app_name' => 'Fixnest',
            'invoice_numbering_reset' => 'financial_year',
            'invoice_start_number' => 50,
        ]);

        $response->assertSessionHasErrors('invoice_start_number');
        $this->assertSame(1, (int) Setting::get('invoice_start_number', 1));
        $this->assertSame(2, $this->seq($this->normalInvoice($company)));

        // Re-submitting the unchanged value (the form always sends it) is fine.
        $this->post(route('settings.update'), [
            'app_name' => 'Fixnest',
            'invoice_numbering_reset' => 'financial_year',
            'invoice_start_number' => 1,
        ])->assertSessionHasNoErrors();
    }

    public function test_start_number_validation(): void
    {
        $this->actingAsAdmin();

        foreach ([0, -5, 'abc', 1.5, 999999999] as $bad) {
            $this->post(route('settings.update'), [
                'app_name' => 'Fixnest',
                'invoice_numbering_reset' => 'financial_year',
                'invoice_start_number' => $bad,
            ])->assertSessionHasErrors('invoice_start_number');
        }
    }

    public function test_yearly_rollover_after_first_sequence_restarts_at_one(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();
        Setting::set('invoice_start_number', '20');

        $this->travelTo(now()->setDate(2026, 5, 1));
        $this->assertSame(20, $this->seq($this->normalInvoice($company)));

        $this->travelTo(now()->setDate(2027, 5, 1)); // next financial year
        $this->assertSame(1, $this->seq($this->normalInvoice($company)));
        $this->travelBack();
    }

    public function test_generator_skips_an_already_taken_number(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();
        $first = $this->normalInvoice($company);           // .../0001
        $reserved = str_replace('/0001', '/0002', $first->invoice_number);
        $first->replicate()->forceFill(['invoice_number' => $reserved])->save(); // occupy 0002

        $this->assertSame(3, $this->seq($this->normalInvoice($company)));
    }

    // ---- Feature 2: random invoices -------------------------------------------------

    public function test_random_page_and_navbar_render_for_admin(): void
    {
        $this->actingAsAdmin();

        $this->get(route('invoices.random.create'))
            ->assertOk()
            ->assertSee('Create Random Invoice')
            ->assertSee('INV/');
        $this->get(route('dashboard'))->assertSee('Random Invoice');
    }

    public function test_random_invoice_is_a_real_record_and_does_not_touch_the_sequence(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();
        Setting::set('invoice_start_number', '20');

        $this->assertSame(20, $this->seq($this->normalInvoice($company)));
        $this->assertSame(21, $this->seq($this->normalInvoice($company)));
        $this->assertSame(22, $this->seq($this->normalInvoice($company)));
        $counterBefore = Organization::current()->next_invoice_number;

        $this->randomInvoice($company, ['random_suffix' => '839274'])->assertSessionHasNoErrors();

        $random = Invoice::latest('id')->first();
        $this->assertMatchesRegularExpression('#^INV/\d{4}(-\d{2})?/839274$#', $random->invoice_number);
        $this->assertSame('draft', $random->status);
        $this->assertSame($company->id, $random->company_id);
        $this->assertSame(1, $random->items()->count());
        $this->assertEqualsWithDelta(118000.00, (float) $random->total_amount, 0.01);
        $this->assertSame('created_random', InvoiceHistory::where('invoice_id', $random->id)->value('action'));
        $this->assertSame($counterBefore, Organization::current()->next_invoice_number);

        $this->assertSame(23, $this->seq($this->normalInvoice($company)));

        // Listing, detail page, PDF.
        $this->get(route('invoices.history'))->assertOk()->assertSee($random->invoice_number);
        $this->get(route('invoices.show', $random))->assertOk()->assertSee('created (random invoice number)');
        $this->get(route('invoices.pdf', $random))->assertOk();
    }

    public function test_blank_suffix_generates_a_unique_random_number(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();

        $this->randomInvoice($company)->assertSessionHasNoErrors();
        $this->randomInvoice($company)->assertSessionHasNoErrors();

        $numbers = Invoice::pluck('invoice_number');
        $this->assertCount(2, $numbers->unique());
        $numbers->each(fn ($n) => $this->assertMatchesRegularExpression('#/\d{6}$#', $n));
    }

    public function test_duplicate_random_number_is_rejected_and_nothing_is_created(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();
        $this->randomInvoice($company, ['random_suffix' => 'ABCD1234'])->assertSessionHasNoErrors();

        $response = $this->randomInvoice($company, ['random_suffix' => 'abcd1234']); // case-insensitive
        $response->assertSessionHasErrors('random_suffix');

        $this->assertSame(1, Invoice::count());
        $this->assertSame(1, \App\Models\SalesOrder::count()); // the second attempt's new order rolled back
    }

    public function test_random_number_may_equal_a_sequential_number_without_clashing(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();
        Setting::set('invoice_start_number', '20');

        $sequential = $this->normalInvoice($company);            // .../0020
        $number = $sequential->invoice_number;
        $this->assertSame('sequential', $sequential->number_source);

        // Same number, entered as a random invoice, is accepted (separate number space).
        $this->randomInvoice($company, ['random_suffix' => '0020'])->assertSessionHasNoErrors();
        $random = Invoice::latest('id')->first();
        $this->assertSame($number, $random->invoice_number);
        $this->assertSame('random', $random->number_source);

        // ...but a second random invoice with that number is still a duplicate.
        $this->randomInvoice($company, ['random_suffix' => '0020'])->assertSessionHasErrors('random_suffix');

        // The sequence is unaffected by the random invoice.
        $this->assertSame(21, $this->seq($this->normalInvoice($company)));
        $this->assertSame(2, Invoice::where('number_source', 'sequential')->count());
        $this->get(route('invoices.history'))->assertOk()->assertSee('Random');
    }

    public function test_duplicate_is_rejected_across_companies(): void
    {
        $this->actingAsAdmin();
        $a = $this->makeCompany();
        $b = Company::create(['name' => 'Other Co', 'address' => 'x', 'email' => 'o@example.com', 'phone' => '1', 'state' => 'Kerala', 'is_active' => true]);

        $this->randomInvoice($a, ['random_suffix' => 'SAME9999'])->assertSessionHasNoErrors();
        $this->randomInvoice($b, ['random_suffix' => 'SAME9999'])->assertSessionHasErrors('random_suffix');
    }

    public function test_invalid_suffix_is_rejected(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();

        foreach (['has space', 'bad/slash', 'waytoolongsuffix1', 'a-b'] as $bad) {
            $this->randomInvoice($company, ['random_suffix' => $bad])->assertSessionHasErrors('random_suffix');
        }
        $this->assertSame(0, Invoice::count());
    }

    public function test_random_invoice_respects_order_capacity(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();

        // Order worth 59,000 but items total 118,000.
        $this->randomInvoice($company, ['new_order_base_value' => 50000])
            ->assertSessionHasErrors('new_order_base_value');
        $this->assertSame(0, Invoice::count());
    }

    public function test_random_draft_does_not_count_as_billed_until_finalized(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();

        $this->randomInvoice($company)->assertSessionHasNoErrors();
        $invoice = Invoice::first();
        $order = $invoice->order;

        $this->assertEqualsWithDelta(0, $order->fresh()->amount_billed, 0.01);

        $this->post(route('invoices.finalize', $invoice))->assertSessionHas('status');
        $this->assertEqualsWithDelta(118000, $order->fresh()->amount_billed, 0.01);
    }

    public function test_finalized_random_invoice_can_be_emailed_and_paid(): void
    {
        $this->actingAsAdmin();
        Mail::fake();
        $company = $this->makeCompany();

        $this->randomInvoice($company)->assertSessionHasNoErrors();
        $invoice = Invoice::first();
        $this->post(route('invoices.finalize', $invoice));

        $this->post(route('invoices.send-email', $invoice), ['sent_to' => 'buyer@example.com'])
            ->assertSessionHasNoErrors();
        $this->assertSame('sent', $invoice->fresh()->status);

        $this->post(route('payments.store', $invoice), ['paid_on' => now()->toDateString(), 'method' => 'UPI'])
            ->assertSessionHas('status');
        $this->assertEqualsWithDelta(0, $invoice->fresh()->load('payments')->amount_outstanding, 0.01);
    }

    public function test_generate_next_invoice_still_uses_the_sequence(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();
        Setting::set('invoice_start_number', '20');

        $first = $this->normalInvoice($company, ['new_order_base_value' => 200000]);
        $this->post(route('invoices.finalize', $first));
        $this->randomInvoice($company)->assertSessionHasNoErrors();

        $order = $first->order;
        $this->post(route('orders.generate-invoice.store', $order), ['amount' => 1000])->assertSessionHasNoErrors();

        $this->assertSame(21, $this->seq(Invoice::latest('id')->first()));
    }

    public function test_database_errors_show_a_friendly_message_and_create_nothing(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();

        $this->mock(\App\Services\RandomInvoiceGenerator::class, function ($mock) {
            $mock->shouldReceive('generateFor')->andThrow(
                new \Illuminate\Database\QueryException('mysql', 'select 1', [], new \Exception('SQLSTATE[42S22] secret sql detail'))
            );
        });

        $response = $this->randomInvoice($company);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringNotContainsString('SQLSTATE', session('error'));
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, \App\Models\SalesOrder::count()); // rolled back with the failed invoice
    }

    public function test_a_duplicate_key_race_shows_a_friendly_number_error(): void
    {
        $this->actingAsAdmin();
        $company = $this->makeCompany();

        $this->mock(\App\Services\RandomInvoiceGenerator::class, function ($mock) {
            $pdo = new \PDOException('Duplicate entry');
            $pdo->errorInfo = ['23000', 1062, 'Duplicate entry'];
            $mock->shouldReceive('generateFor')->andThrow(new \Illuminate\Database\QueryException('mysql', 'insert', [], $pdo));
        });

        $this->randomInvoice($company, ['random_suffix' => '777'])->assertSessionHasErrors('random_suffix');
        $this->assertSame(0, Invoice::count());
    }

    public function test_random_routes_and_start_number_require_admin(): void
    {
        $user = $this->makeUser(); // no admin role
        $this->actingAs($user);
        $company = $this->makeCompany();

        $this->get(route('invoices.random.create'))->assertForbidden();
        $this->randomInvoice($company)->assertForbidden();
        $this->post(route('settings.update'), ['app_name' => 'x', 'invoice_numbering_reset' => 'never', 'invoice_start_number' => 5])->assertForbidden();
        $this->assertSame(0, Invoice::count());
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('invoices.random.create'))->assertRedirect(route('login'));
    }
}

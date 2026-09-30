<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
class SettingsController extends Controller
{
    protected function defaults(): array
    {
        return [
            'app_name' => 'Fixnest',
            'invoice_numbering_reset' => 'financial_year',
            'invoice_start_number' => '1',
            'pdf_show_logo' => '1',
            'pdf_show_signature' => '1',
            'pdf_footer_note' => 'Thank you for your business!',
        ];
    }
    public function edit(): View
    {
        $settings = Setting::getMany($this->defaults());
        $organization = Organization::current();
        $sequenceStarted = $organization->invoiceSequenceStarted();
        $nextInvoiceNumber = $organization->next_invoice_number;

        return view('settings.edit', compact('settings', 'sequenceStarted', 'nextInvoiceNumber'));
    }
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'invoice_numbering_reset' => ['required', 'in:financial_year,calendar_year,never'],
            'invoice_start_number' => ['nullable', 'integer', 'min:1', 'max:99999999'],
            'pdf_show_logo' => ['nullable', 'boolean'],
            'pdf_show_signature' => ['nullable', 'boolean'],
            'pdf_footer_note' => ['nullable', 'string', 'max:500'],
        ]);
        $validated['pdf_show_logo'] = $request->boolean('pdf_show_logo') ? '1' : '0';
        $validated['pdf_show_signature'] = $request->boolean('pdf_show_signature') ? '1' : '0';
        // The starting number is never written blindly: it only seeds a sequence that
        // hasn't issued a number yet, and is locked afterwards. Handled under the same
        // organization row lock the invoice number generator takes, so it can't race
        // with an invoice being created at the same moment.
        $startNumber = $validated['invoice_start_number'] ?? null;
        unset($validated['invoice_start_number']);

        DB::transaction(function () use ($validated, $startNumber) {
            $organization = Organization::where('id', Organization::current()->id)->lockForUpdate()->first();

            if ($organization->invoiceSequenceStarted()) {
                $current = max(1, (int) Setting::get('invoice_start_number', 1));

                if ($startNumber !== null && (int) $startNumber !== $current) {
                    throw ValidationException::withMessages([
                        'invoice_start_number' => 'The starting invoice number can no longer be changed — invoice numbering has already started (next number: ' . $organization->next_invoice_number . ').',
                    ]);
                }
            } else {
                Setting::set('invoice_start_number', (string) ($startNumber ?? 1));
            }

            foreach ($validated as $key => $value) {
                Setting::set($key, $value);
            }
        });
        return back()->with('status', 'Settings saved.');
    }
}
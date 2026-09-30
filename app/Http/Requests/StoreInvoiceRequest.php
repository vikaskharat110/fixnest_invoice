<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        // Sales order linkage is required when creating a new invoice, but locked
        // (and not re-validated) once the invoice exists — same treatment as company_id.
        $isCreating = $this->isMethod('POST');
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'order_mode' => [$isCreating ? 'required' : 'nullable', Rule::in(['existing', 'new'])],
            'order_id' => [
                ...($isCreating ? ['required_if:order_mode,existing'] : []),
                'nullable',
                Rule::exists('sales_orders', 'id')->where('company_id', $this->input('company_id')),
            ],
            'new_order_base_value' => [
                ...($isCreating ? ['required_if:order_mode,new'] : []),
                'nullable', 'numeric', 'min:0.01',
            ],
            'new_order_tax_percent' => [
                ...($isCreating ? ['required_if:order_mode,new'] : []),
                'nullable', 'numeric', 'min:0', 'max:100',
            ],
            'new_order_description' => ['nullable', 'string'],
            'invoice_type' => ['required', Rule::in(['invoice', 'tax_invoice'])],
            'template' => ['nullable', Rule::in(['classic', 'modern'])],
            'gst_type' => ['required', Rule::in(['gst', 'non_gst'])],
            'tax_type' => ['required_if:gst_type,gst', 'nullable', Rule::in(['cgst_sgst', 'igst'])],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'other_references' => ['nullable', 'string', 'max:255'],
            'delivery_note' => ['nullable', 'string', 'max:255'],
            'buyer_order_number' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_description' => ['required', 'string', 'max:255'],
            'items.*.hsn_sac' => ['nullable', 'string', 'max:20'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.rate' => ['required', 'numeric', 'min:0'],
            'items.*.tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
    public function messages(): array
    {
        return [
            'order_id.required_if' => 'Please select a sales order.',
            'new_order_base_value.required_if' => 'Please enter the base value of the new order.',
            'new_order_tax_percent.required_if' => 'Please enter the tax % for the new order.',
            'items.required' => 'Add at least one line item.',
            'items.min' => 'Add at least one line item.',
        ];
    }
}
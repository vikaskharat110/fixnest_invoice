<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNextInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\SalesOrder $order */
        $order = $this->route('order');

        // Small epsilon to tolerate decimal rounding noise, matching the
        // tolerance InvoiceController::assertWithinOrderCapacity() already uses.
        $remaining = (float) $order->amount_remaining + 0.01;

        return [
            'amount' => ['required', 'numeric', 'min:0.01', "max:{$remaining}"],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.max' => 'This amount exceeds the sales order\'s remaining value.',
        ];
    }
}

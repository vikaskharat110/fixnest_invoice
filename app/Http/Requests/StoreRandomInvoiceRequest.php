<?php
namespace App\Http\Requests;

/**
 * Same rules as a normal invoice, plus the optional random-number suffix.
 * Blank suffix = generate one automatically.
 */
class StoreRandomInvoiceRequest extends StoreInvoiceRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'random_suffix' => ['nullable', 'string', 'regex:/^[A-Za-z0-9]{1,12}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'random_suffix.regex' => 'Use 1–12 letters or digits only (no spaces or symbols).',
        ];
    }
}

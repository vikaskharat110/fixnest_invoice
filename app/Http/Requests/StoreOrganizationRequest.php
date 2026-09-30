<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'org_name' => ['nullable', 'string', 'max:255'],
            'org_gst_number' => ['nullable', 'string', 'max:15'],
            'org_pan_number' => ['nullable', 'string', 'max:10'],
            'org_support_email' => ['nullable', 'email', 'max:255'],
            'org_address' => ['nullable', 'string'],
            'org_bank_details' => ['nullable', 'string'],
            'org_invoice_prefix' => ['nullable', 'string', 'max:20'],
            'org_logo' => ['nullable', 'image', 'max:2048'],
            'org_signature' => ['nullable', 'image', 'max:1024'],
        ];
    }
}

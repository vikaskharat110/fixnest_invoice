<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class OrganizationController extends Controller
{
    public function update(StoreOrganizationRequest $request): RedirectResponse
    {
        $organization = Organization::current();
        $validated = $request->validated();

        $data = [
            'name' => $validated['org_name'] ?? null,
            'address' => $validated['org_address'] ?? null,
            'gst_number' => $validated['org_gst_number'] ?? null,
            'pan_number' => $validated['org_pan_number'] ?? null,
            'support_email' => $validated['org_support_email'] ?? null,
            'bank_details' => $validated['org_bank_details'] ?? null,
            'invoice_prefix' => $validated['org_invoice_prefix'] ?? null,
        ];

        if ($request->hasFile('org_logo')) {
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }
            $data['logo_path'] = $request->file('org_logo')->store('logos', 'public');
        }

        if ($request->hasFile('org_signature')) {
            if ($organization->signature_path) {
                Storage::disk('public')->delete($organization->signature_path);
            }
            $data['signature_path'] = $request->file('org_signature')->store('signatures', 'public');
        }

        $organization->update($data);

        return redirect()->route('profile.edit')
            ->with('status', 'organization-updated');
    }
}

<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Produces invoice numbers for the separate "Random Invoice" feature.
 *
 * Format: PREFIX/<year label>/<number entered by the admin>  e.g. INV/2026-27/839274
 * (a blank entry gets an auto-generated 6-digit number).
 *
 * This class never reads or writes the organization's sequential counter.
 * Random and sequential invoices live in the same table but are separate number
 * spaces (invoices.number_source), so a random number may equal a sequential one;
 * only another random invoice with the same number is a duplicate.
 */
class RandomInvoiceGenerator
{
    private const MAX_ATTEMPTS = 20;

    public function __construct(private InvoiceNumberGenerator $numberGenerator) {}

    /**
     * Number prefix shown in the UI, e.g. "INV/2026-27/".
     */
    public function prefixFor(Organization $organization): string
    {
        return sprintf('%s/%s/', $organization->invoice_prefix, $this->numberGenerator->yearLabel());
    }

    /**
     * Must be called inside the caller's DB transaction. Takes the same
     * organization row lock as the sequential generator, so concurrent
     * random and sequential creations are serialized and the existence check
     * below cannot race.
     *
     * @throws ValidationException when an admin-supplied suffix is already taken
     */
    public function generateFor(Organization $organization, ?string $suffix = null): string
    {
        return DB::transaction(function () use ($organization, $suffix) {
            Organization::where('id', $organization->id)->lockForUpdate()->first();

            $prefix = $this->prefixFor($organization);

            if ($suffix !== null && $suffix !== '') {
                $candidate = $prefix . strtoupper($suffix);

                if ($this->exists($candidate)) {
                    throw ValidationException::withMessages([
                        'random_suffix' => "Random invoice number {$candidate} already exists. Enter a different number or leave it blank to generate one automatically.",
                    ]);
                }

                return $candidate;
            }

            for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
                $candidate = $prefix . random_int(100000, 999999);

                if (! $this->exists($candidate)) {
                    return $candidate;
                }
            }

            throw ValidationException::withMessages([
                'random_suffix' => 'Could not generate a unique random invoice number. Please try again.',
            ]);
        });
    }

    protected function exists(string $invoiceNumber): bool
    {
        // withTrashed: soft-deleted drafts still hold their number (the unique index covers them).
        return Invoice::withTrashed()->where('number_source', 'random')->where('invoice_number', $invoiceNumber)->exists();
    }
}

<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class InvoiceNumberGenerator
{
    public function generateFor(Organization $organization): string
    {
        return DB::transaction(function () use ($organization) {
            $locked = Organization::where('id', $organization->id)->lockForUpdate()->first();

            $resetMode = Setting::get('invoice_numbering_reset', 'financial_year');
            [$yearLabel, $periodKey] = $this->resolvePeriod($resetMode);

            // Evaluated before any mutation below: the admin-configured starting
            // number only seeds a sequence that has never issued a number.
            $notStarted = ! $locked->invoiceSequenceStarted();

            if ($resetMode !== 'never' && $locked->invoice_number_period !== $periodKey) {
                $locked->next_invoice_number = $notStarted ? $this->startingNumber() : 1;
                $locked->invoice_number_period = $periodKey;
            } elseif ($notStarted) {
                // 'never' mode: no period rollover ever fires, so seed the counter here.
                $locked->next_invoice_number = $this->startingNumber();
            }

            // Skip past any sequential number that is already taken (including
            // soft-deleted invoices), so the sequence can never collide with an
            // existing sequential record. Random invoices are a separate number space.
            do {
                $number = $locked->next_invoice_number;
                $locked->next_invoice_number = $number + 1;

                $candidate = sprintf(
                    '%s/%s/%04d',
                    $locked->invoice_prefix,
                    $yearLabel,
                    $number
                );
            } while (Invoice::withTrashed()->where('number_source', 'sequential')->where('invoice_number', $candidate)->exists());

            $locked->save();

            return $candidate;
        });
    }

    /**
     * Year label used inside invoice numbers (e.g. "2026-27"), following the
     * same reset-mode setting as the sequential numbers.
     */
    public function yearLabel(): string
    {
        return $this->resolvePeriod(Setting::get('invoice_numbering_reset', 'financial_year'))[0];
    }

    protected function startingNumber(): int
    {
        return max(1, (int) Setting::get('invoice_start_number', 1));
    }

    protected function resolvePeriod(string $resetMode): array
    {
        $now = now();

        if ($resetMode === 'financial_year') {
            // Indian financial year: 1 Apr – 31 Mar
            $fyStart = $now->month >= 4 ? $now->year : $now->year - 1;
            $fyEnd = $fyStart + 1;
            $label = $fyStart . '-' . substr((string) $fyEnd, -2);
            return [$label, 'FY' . $fyStart];
        }

        if ($resetMode === 'calendar_year') {
            return [(string) $now->year, 'CY' . $now->year];
        }

        // 'never' — keeps incrementing forever, matching the original behaviour.
        return [(string) $now->year, 'NEVER'];
    }
}

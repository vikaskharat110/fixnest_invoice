<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Until now uniqueness was only enforced per company. Invoice numbers now come
        // from one organization-wide counter (plus random-invoice numbers), so they must
        // be unique across the whole table. Legacy data from the old per-company
        // counters may contain cross-company duplicates; never fail a deploy over that —
        // skip the index, log it, and rely on the generators' own existence checks.
        $hasDuplicates = DB::table('invoices')
            ->select('invoice_number')
            ->groupBy('invoice_number')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            Log::warning('invoices.invoice_number global unique index NOT created: duplicate invoice numbers exist. Resolve them and re-run this migration.');

            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('invoice_number', 'invoices_invoice_number_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasIndex('invoices', 'invoices_invoice_number_unique')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropUnique('invoices_invoice_number_unique');
            });
        }
    }
};

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
        // Sequential and random invoice numbers are two independent number spaces
        // inside the same invoices table, so uniqueness is scoped per number_source.
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('number_source', 20)->default('sequential')->after('invoice_number');
        });

        // The per-company unique index is replaced first-in-line by a source-aware one
        // (added before the old one is dropped, so the company_id foreign key always
        // has a usable index).
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['company_id', 'invoice_number', 'number_source'], 'invoices_company_number_source_unique');
        });

        if (Schema::hasIndex('invoices', 'invoices_company_id_invoice_number_unique')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropUnique('invoices_company_id_invoice_number_unique');
            });
        }

        // The global unique index from the previous migration ignores number_source.
        if (Schema::hasIndex('invoices', 'invoices_invoice_number_unique')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropUnique('invoices_invoice_number_unique');
            });
        }

        $hasDuplicates = DB::table('invoices')
            ->select('invoice_number', 'number_source')
            ->groupBy('invoice_number', 'number_source')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            Log::warning('invoices (invoice_number, number_source) global unique index NOT created: duplicate invoice numbers exist. Resolve them and re-run this migration.');

            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['invoice_number', 'number_source'], 'invoices_number_source_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasIndex('invoices', 'invoices_number_source_unique')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropUnique('invoices_number_source_unique');
            });
        }

        // Random invoices may share a number with a sequential one; they would violate
        // the restored per-company index, so rollback is only safe without such rows.
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['company_id', 'invoice_number'], 'invoices_company_id_invoice_number_unique');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_company_number_source_unique');
            $table->dropColumn('number_source');
        });
    }
};

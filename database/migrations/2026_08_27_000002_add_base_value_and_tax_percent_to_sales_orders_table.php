<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->decimal('base_value', 14, 2)->nullable()->after('order_number');
            $table->decimal('tax_percent', 5, 2)->nullable()->after('base_value');
        });

        // Backfill: treat any existing order's current total_value as its base
        // value with 0% tax, so nothing already saved silently changes what's
        // owed. New orders going forward populate these properly at creation.
        DB::table('sales_orders')->whereNull('base_value')->update([
            'base_value' => DB::raw('total_value'),
            'tax_percent' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['base_value', 'tax_percent']);
        });
    }
};
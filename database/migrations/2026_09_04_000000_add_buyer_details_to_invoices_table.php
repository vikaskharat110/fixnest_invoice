<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('buyer_name')->nullable()->after('customer_id');
            $table->string('buyer_email')->nullable()->after('buyer_name');
            $table->string('buyer_phone')->nullable()->after('buyer_email');
            $table->text('buyer_address')->nullable()->after('buyer_phone');
            $table->string('buyer_gstin')->nullable()->after('buyer_address');
            $table->string('buyer_state')->nullable()->after('buyer_gstin');
        });

        // Backfill from the customers table before it disappears, so historical
        // invoices keep their "Bill To" details.
        DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->update([
                'invoices.buyer_name' => DB::raw('customers.name'),
                'invoices.buyer_email' => DB::raw('customers.email'),
                'invoices.buyer_phone' => DB::raw('customers.phone'),
                'invoices.buyer_address' => DB::raw('customers.address'),
                'invoices.buyer_gstin' => DB::raw('customers.gstin'),
                'invoices.buyer_state' => DB::raw('customers.state'),
            ]);

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('company_id')->constrained()->restrictOnDelete();
            $table->dropColumn([
                'buyer_name',
                'buyer_email',
                'buyer_phone',
                'buyer_address',
                'buyer_gstin',
                'buyer_state',
            ]);
        });
    }
};

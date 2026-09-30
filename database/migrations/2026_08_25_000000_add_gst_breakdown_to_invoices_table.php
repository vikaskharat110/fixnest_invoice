<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('other_references')->nullable()->after('reference_number');
            $table->enum('tax_type', ['cgst_sgst', 'igst'])->default('cgst_sgst')->after('gst_type');

            $table->decimal('cgst_rate', 5, 2)->default(0)->after('tax_amount');
            $table->decimal('cgst_amount', 12, 2)->default(0)->after('cgst_rate');
            $table->decimal('sgst_rate', 5, 2)->default(0)->after('cgst_amount');
            $table->decimal('sgst_amount', 12, 2)->default(0)->after('sgst_rate');
            $table->decimal('igst_rate', 5, 2)->default(0)->after('sgst_amount');
            $table->decimal('igst_amount', 12, 2)->default(0)->after('igst_rate');

            $table->text('tax_amount_in_words')->nullable()->after('amount_in_words');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'other_references',
                'tax_type',
                'cgst_rate', 'cgst_amount',
                'sgst_rate', 'sgst_amount',
                'igst_rate', 'igst_amount',
                'tax_amount_in_words',
            ]);
        });
    }
};
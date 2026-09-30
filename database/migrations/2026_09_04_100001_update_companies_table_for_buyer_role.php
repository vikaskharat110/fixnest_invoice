<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('email')->nullable()->after('address');
            $table->string('phone')->nullable()->after('email');
            $table->string('state')->nullable()->after('phone');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'signature_path',
                'bank_details',
                'invoice_prefix',
                'template_id',
                'next_invoice_number',
                'invoice_number_period',
                'next_order_number',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone', 'state']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('logo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->text('bank_details')->nullable();
            $table->string('invoice_prefix', 20)->nullable();
            $table->string('template_id')->nullable();
            $table->unsignedInteger('next_invoice_number')->default(1);
            $table->string('invoice_number_period')->nullable();
            $table->unsignedInteger('next_order_number')->default(1);
        });
    }
};

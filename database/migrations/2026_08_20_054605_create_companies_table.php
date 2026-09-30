<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('gst_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->text('address');
            $table->string('logo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('bank_details')->nullable();
            $table->string('invoice_prefix', 20);
            $table->string('template_id')->nullable();
            $table->unsignedInteger('next_invoice_number')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

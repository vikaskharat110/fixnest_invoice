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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            $table->string('invoice_number');
            $table->enum('invoice_type', ['invoice', 'tax_invoice']);
            $table->enum('gst_type', ['gst', 'non_gst']);
            $table->enum('status', ['draft', 'finalized', 'sent', 'archived'])->default('draft');

            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('delivery_note')->nullable();
            $table->string('buyer_order_number')->nullable();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('amount_in_words')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'invoice_number']);
            $table->index(['status', 'invoice_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};

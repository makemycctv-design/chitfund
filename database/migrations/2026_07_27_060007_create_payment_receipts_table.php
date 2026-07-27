<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A receipt issued for a successful payment transaction. Receipt numbers are
 * unique per company and human-friendly (e.g. RCPT-2026-000123). The generated
 * PDF path is stored once rendered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_transaction_id')->unique()->constrained('payment_transactions')->cascadeOnDelete();

            $table->string('receipt_number');
            $table->decimal('amount', 15, 2);
            $table->timestamp('issued_at');

            $table->string('pdf_path')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'receipt_number']);
            $table->index(['company_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
    }
};

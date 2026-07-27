<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A money event: an online gateway attempt or a manually-recorded cash/bank
 * collection. This is the immutable-ish source of truth for reconciliation.
 *
 * `reference` is our internal unique id; `idempotency_key` guards against
 * duplicate creation; gateway_* hold the provider's identifiers/signature.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('chitty_id')->nullable()->constrained()->nullOnDelete();

            // manual | razorpay | cashfree | phonepe | stripe
            $table->string('gateway', 30);
            // cash | upi | card | netbanking | wallet | bank_transfer
            $table->string('method', 30);

            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('INR');

            // initiated | pending | success | failed | refunded | disputed
            $table->string('status', 20)->default('initiated');

            $table->string('reference')->unique();
            $table->string('idempotency_key')->nullable()->unique();

            $table->string('gateway_order_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->text('gateway_signature')->nullable();

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); // manual entry staff
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();  // reconciliation approver
            $table->timestamp('reconciled_at')->nullable();

            $table->text('failure_reason')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('gateway_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};

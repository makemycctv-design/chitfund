<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prize payout to an auction winner. Uses a maker-checker flow: created pending
 * at finalization, then approved and marked paid by authorized staff.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prize_payouts', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('auction_result_id')->constrained('auction_results')->cascadeOnDelete();
            $table->foreignId('chitty_membership_id')->constrained('chitty_memberships')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();

            $table->decimal('amount', 15, 2);

            // pending | approved | paid | rejected
            $table->string('status', 20)->default('pending');

            $table->foreignId('customer_bank_account_id')->nullable()->constrained('customer_bank_accounts')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('reference')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prize_payouts');
    }
};

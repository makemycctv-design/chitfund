<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable bid ledger. Every accepted bid is written here with a SERVER
 * timestamp; rejected attempts may also be recorded (is_valid=false) for audit.
 * Rows are never updated except to flip is_winning at finalization.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_bids', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('auction_participant_id')->constrained('auction_participants')->cascadeOnDelete();
            $table->foreignId('chitty_membership_id')->constrained('chitty_memberships')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();

            $table->decimal('amount', 15, 2); // discount amount (for max_discount)

            $table->boolean('is_valid')->default(true);
            $table->boolean('is_winning')->default(false);
            $table->string('rejection_reason')->nullable();

            $table->string('idempotency_key')->nullable();
            $table->timestamp('server_placed_at');
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['auction_id', 'idempotency_key']);
            $table->index(['auction_id', 'is_valid', 'amount']);
            $table->index(['company_id', 'auction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_bids');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The finalized, published outcome of an auction. All figures are computed
 * server-side at finalization and are the source of truth for payouts and
 * dividend distribution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_results', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('auction_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('chitty_id')->constrained()->cascadeOnDelete();

            $table->foreignId('winner_membership_id')->nullable()->constrained('chitty_memberships')->nullOnDelete();
            $table->foreignId('winner_customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('winning_bid_id')->nullable();

            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('prize_amount', 15, 2)->default(0);
            $table->decimal('foreman_commission', 15, 2)->default(0);
            $table->decimal('distributable_dividend', 15, 2)->default(0);
            $table->decimal('dividend_per_member', 15, 2)->default(0);

            $table->timestamp('published_at')->nullable();
            $table->string('report_path')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'chitty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_results');
    }
};

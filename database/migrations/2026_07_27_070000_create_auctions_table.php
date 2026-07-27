<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An auction event held for one period of a chitty. Financial parameters are
 * snapshotted at scheduling time so later scheme/chitty edits never change a
 * historical auction. All timing is server-authoritative (ends_at, extensions).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chitty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('installment_schedule_id')->nullable()->constrained('installment_schedules')->nullOnDelete();

            $table->unsignedSmallInteger('period_no');

            // scheduled | live | paused | closed | cancelled
            $table->string('status', 20)->default('scheduled');
            // max_discount | min_prize | lowest_bid  (business-configurable)
            $table->string('method', 20)->default('max_discount');

            // Snapshots used for winner/prize/dividend math.
            $table->decimal('chit_value', 15, 2);
            $table->decimal('foreman_commission_percent', 5, 2)->default(0);
            $table->unsignedSmallInteger('total_subscribers');

            // Bidding bounds are absolute money amounts (e.g. discount amount).
            $table->decimal('min_bid_amount', 15, 2)->default(0);
            $table->decimal('max_bid_amount', 15, 2);
            $table->decimal('bid_increment', 15, 2)->default(0);

            $table->timestamp('scheduled_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('paused_at')->nullable();

            // Anti-sniping auto-extension.
            $table->unsignedInteger('auto_extend_window_seconds')->default(0);
            $table->unsignedInteger('auto_extend_seconds')->default(0);
            $table->unsignedSmallInteger('extended_count')->default(0);

            // Outcome (populated at finalization).
            $table->foreignId('winner_membership_id')->nullable()->constrained('chitty_memberships')->nullOnDelete();
            $table->unsignedBigInteger('winning_bid_id')->nullable(); // no FK (circular with auction_bids)
            $table->decimal('prize_amount', 15, 2)->nullable();
            $table->decimal('foreman_commission', 15, 2)->nullable();
            $table->decimal('dividend_per_member', 15, 2)->nullable();

            $table->text('cancelled_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['chitty_id', 'period_no']);
            $table->index(['company_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};

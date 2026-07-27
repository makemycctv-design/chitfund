<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chitty-level schedule: one row per period (month) of a chitty. Drives due
 * dates and links to the auction held for that period (Phase 3). Per-subscriber
 * obligations live in installment_payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_schedules', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chitty_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('period_no'); // 1..duration_months
            $table->date('due_date');
            $table->decimal('base_installment_amount', 15, 2);

            // pending | collecting | auctioned | closed
            $table->string('status', 20)->default('pending');

            // Set once the auction for this period is finalized (Phase 3).
            $table->unsignedBigInteger('auction_id')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['chitty_id', 'period_no']);
            $table->index(['company_id', 'chitty_id']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_schedules');
    }
};

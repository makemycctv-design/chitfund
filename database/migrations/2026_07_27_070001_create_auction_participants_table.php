<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eligibility + presence record for a member in an auction. Eligibility is
 * computed server-side before entry; presence is refreshed by the client
 * (heartbeat / poll) so the room can show who is active.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_participants', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chitty_membership_id')->constrained('chitty_memberships')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();

            $table->boolean('is_eligible')->default(false);
            $table->string('ineligible_reason')->nullable();

            $table->timestamp('joined_at')->nullable();
            $table->boolean('is_present')->default(false);
            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            $table->unique(['auction_id', 'chitty_membership_id']);
            $table->index(['company_id', 'auction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_participants');
    }
};

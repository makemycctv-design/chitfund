<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-channel delivery record for every notification attempt. Used for the
 * admin notification log, delivery-status tracking, and retries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('event_key');
            $table->string('channel', 20);
            $table->string('notifiable_type')->nullable();

            // pending | sent | failed | skipped
            $table->string('status', 20)->default('pending');
            $table->string('provider_message_id')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();

            $table->json('payload')->nullable(); // rendered subject/body snapshot
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'channel', 'status']);
            $table->index(['user_id', 'event_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};

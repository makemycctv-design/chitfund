<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Raw inbound gateway webhook events. The unique (gateway, event_id) constraint
 * is the idempotency guard so a redelivered webhook is never processed twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();

            $table->string('gateway', 30);
            $table->string('event_id');   // provider event id
            $table->string('type')->nullable();

            $table->longText('payload'); // full JSON payload as received

            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();

            // received | processed | failed | ignored
            $table->string('status', 20)->default('received');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();

            $table->timestamps();

            $table->unique(['gateway', 'event_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};

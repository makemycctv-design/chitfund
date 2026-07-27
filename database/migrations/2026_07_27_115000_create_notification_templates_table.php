<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configurable notification templates per event + channel. A null company_id is
 * a global default; a company row overrides it. Bodies use {{variable}} tokens
 * rendered at send time. WhatsApp rows also track the provider template's
 * approval status.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('event_key');   // e.g. payment.received, kyc.approved
            $table->string('channel', 20);  // database | mail | whatsapp | push | sms
            $table->string('name');

            $table->string('subject')->nullable();  // mail subject / in-app title
            $table->text('body');                    // supports {{variables}}

            $table->boolean('is_active')->default(true);

            // WhatsApp Cloud API template governance.
            $table->string('whatsapp_template_name')->nullable();
            $table->string('whatsapp_approval_status', 20)->nullable(); // pending|approved|rejected

            $table->json('variables')->nullable(); // documented available tokens

            $table->timestamps();

            $table->unique(['company_id', 'event_key', 'channel']);
            $table->index('event_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};

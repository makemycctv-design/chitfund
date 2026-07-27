<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configurable key/value settings. A null company_id means a global/default
 * setting; a company_id scopes the setting to that company. This keeps all
 * compliance-related knobs configurable per company and per state policy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('group')->default('general'); // e.g. general, compliance, payment, notification
            $table->string('key');
            $table->longText('value')->nullable(); // JSON-encoded value
            $table->string('type', 20)->default('string'); // string|int|bool|json|decimal
            $table->boolean('is_public')->default(false); // safe to expose to frontend?

            $table->timestamps();

            $table->unique(['company_id', 'group', 'key']);
            $table->index(['company_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

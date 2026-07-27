<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a customer to a chitty (their "ticket"/slot). Phase 1 establishes the
 * foundational table so dashboards can report real subscriber counts; the full
 * enrollment, allocation and replacement workflow is built in Phase 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chitty_memberships', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chitty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedSmallInteger('ticket_number')->nullable(); // slot within the chitty

            // pending | active | prized | defaulted | terminated | replaced
            $table->string('status', 20)->default('active');

            $table->date('joined_on')->nullable();
            $table->boolean('is_prized')->default(false); // has won an auction
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['chitty_id', 'ticket_number']);
            $table->unique(['chitty_id', 'customer_id']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chitty_memberships');
    }
};

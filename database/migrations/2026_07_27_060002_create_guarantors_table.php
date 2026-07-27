<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Surety/guarantor details attached to a chitty membership, required by many
 * chitty companies before a subscriber can bid/receive a prize.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guarantors', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chitty_membership_id')->constrained('chitty_memberships')->cascadeOnDelete();

            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('relationship')->nullable();
            $table->text('address')->nullable();
            $table->string('id_proof_type', 30)->nullable();
            $table->text('id_proof_number')->nullable(); // encrypted

            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'chitty_membership_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guarantors');
    }
};

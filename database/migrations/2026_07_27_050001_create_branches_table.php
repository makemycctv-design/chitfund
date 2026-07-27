<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branches belong to a company. Staff and customers may be scoped to a branch,
 * and chitties are always operated out of a branch. This is the unit used for
 * "view only assigned branches" style permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('code'); // unique per company (enforced below)
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();

            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode', 10)->nullable();

            // Branch manager is a user; nullable + nullOnDelete so we can seed
            // the branch before the manager user exists.
            $table->foreignId('manager_id')->nullable();

            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};

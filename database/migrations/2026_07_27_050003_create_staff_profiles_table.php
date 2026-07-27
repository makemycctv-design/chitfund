<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-to-one profile for back-office (staff) users. Roles/permissions live in
 * the Spatie tables; this holds HR/operational attributes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            $table->string('employee_code')->nullable();
            $table->string('designation')->nullable();
            $table->date('joined_on')->nullable();

            // active | suspended
            $table->string('status', 20)->default('active');

            $table->boolean('two_factor_enabled')->default(false);
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id');
            $table->unique(['company_id', 'employee_code']);
            $table->index(['company_id', 'branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One-to-one profile for customer/subscriber users. Captures the registration
 * approval workflow state and KYC status. Detailed KYC documents and bank
 * accounts are separate tables (added in Phase 2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            $table->string('customer_code')->nullable();

            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('occupation')->nullable();
            $table->decimal('annual_income', 15, 2)->nullable();

            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode', 10)->nullable();

            // Registration approval workflow: pending | approved | rejected
            $table->string('registration_status', 20)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // KYC workflow: pending | submitted | verified | rejected
            $table->string('kyc_status', 20)->default('pending');
            $table->unsignedTinyInteger('kyc_level')->default(0);
            $table->timestamp('kyc_verified_at')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('user_id');
            $table->unique(['company_id', 'customer_code']);
            $table->index(['company_id', 'branch_id']);
            $table->index('registration_status');
            $table->index('kyc_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};

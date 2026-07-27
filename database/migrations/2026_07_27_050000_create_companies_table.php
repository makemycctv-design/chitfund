<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Companies are the top-level tenant boundary. Every branch, staff member,
 * customer, scheme and chitty ultimately belongs to a company. This enables
 * the platform to run as a single installation today and as a multi-company
 * SaaS later without schema changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique(); // public-facing identifier

            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('registration_number')->nullable(); // Chit Fund registration no.
            $table->string('gstin', 20)->nullable();

            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();

            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable(); // compliance rules can vary by state
            $table->string('pincode', 10)->nullable();
            $table->string('country', 2)->default('IN');

            $table->string('currency', 3)->default('INR');
            $table->string('timezone', 64)->default('Asia/Kolkata');
            $table->string('locale', 5)->default('en'); // en | ml

            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // company-level configurable compliance settings

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

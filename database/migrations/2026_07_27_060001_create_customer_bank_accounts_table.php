<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer bank accounts used for prize payouts / refunds. The full account
 * number is stored ENCRYPTED at rest (model cast); only the last 4 digits are
 * kept in clear for display. Verification is a maker-checker style workflow.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();

            $table->string('account_holder_name');
            $table->text('account_number');           // encrypted
            $table->string('account_number_last4', 4)->nullable();
            $table->string('ifsc', 20);
            $table->string('bank_name');
            $table->string('branch_name')->nullable();

            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_bank_accounts');
    }
};

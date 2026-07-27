<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extend the starter-kit users table with the columns the Chitty Fund domain
 * needs: tenant scoping (company/branch), a public ULID, phone (used for OTP
 * login on mobile), an account type discriminator, and lifecycle flags.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->ulid('ulid')->nullable()->unique()->after('id');

            $table->foreignId('company_id')->nullable()->after('ulid')
                ->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('company_id')
                ->constrained()->nullOnDelete();

            // 'staff' users access the admin/back-office; 'customer' users access
            // the customer portal and the mobile app.
            $table->string('type', 20)->default('customer')->after('email');
            $table->string('phone', 20)->nullable()->unique()->after('type');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');

            $table->string('locale', 5)->default('en')->after('phone_verified_at');
            $table->boolean('is_active')->default(true)->after('locale');
            $table->timestamp('last_login_at')->nullable();

            $table->softDeletes();

            $table->index(['company_id', 'branch_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn([
                'ulid', 'type', 'phone', 'phone_verified_at',
                'locale', 'is_active', 'last_login_at', 'deleted_at',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records each successful login for staff/customer accounts (security + staff
 * activity oversight).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('logged_in_at');

            $table->index(['user_id', 'logged_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-subscriber, per-period obligation and its payment state. Created for each
 * active membership when a chitty's schedule is generated. A successful payment
 * transaction settles one (or more) of these rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chitty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chitty_membership_id')->constrained('chitty_memberships')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('installment_schedule_id')->constrained('installment_schedules')->cascadeOnDelete();

            $table->unsignedSmallInteger('period_no');
            $table->date('due_date');

            $table->decimal('amount_due', 15, 2);
            $table->decimal('late_fee', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);

            // pending | partial | paid | overdue | waived
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();

            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();

            $table->foreignId('waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('waiver_reason')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['chitty_membership_id', 'period_no']);
            $table->index(['company_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['chitty_id', 'period_no']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_payments');
    }
};

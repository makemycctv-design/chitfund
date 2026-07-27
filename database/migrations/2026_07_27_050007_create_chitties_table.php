<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A chitty is a running instance of a scheme, operated out of a branch. It owns
 * its full lifecycle (draft -> open_for_enrollment -> active -> ... -> closed)
 * and carries a frozen copy of its financial terms.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chitties', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chitty_scheme_id')->nullable()->constrained()->nullOnDelete();

            $table->string('code');
            $table->string('name');

            $table->decimal('chit_value', 15, 2);
            $table->unsignedSmallInteger('duration_months');
            $table->unsignedSmallInteger('total_subscribers');
            $table->decimal('installment_amount', 15, 2);

            $table->decimal('foreman_commission_percent', 5, 2)->default(0);
            $table->string('auction_frequency', 20)->default('monthly');

            $table->decimal('min_bid_percent', 5, 2)->nullable();
            $table->decimal('max_bid_percent', 5, 2)->nullable();

            $table->unsignedSmallInteger('grace_period_days')->default(0);
            $table->string('late_fee_type', 20)->default('none');
            $table->decimal('late_fee_value', 15, 2)->default(0);
            $table->unsignedTinyInteger('required_kyc_level')->default(1);

            $table->date('enrollment_opens_on')->nullable();
            $table->date('enrollment_closes_on')->nullable();
            $table->date('start_date')->nullable();
            $table->date('maturity_date')->nullable();

            $table->unsignedTinyInteger('auction_day')->nullable(); // day of month
            $table->time('auction_time')->nullable();

            // draft | open_for_enrollment | active | auction_scheduled |
            // auction_running | closed | cancelled
            $table->string('status', 30)->default('draft');

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->json('meta')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'branch_id', 'status']);
            $table->index('status');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chitties');
    }
};

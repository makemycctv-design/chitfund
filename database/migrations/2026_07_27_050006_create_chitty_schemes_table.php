<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A chitty scheme is a reusable template/category (e.g. "1 Lakh / 20 months").
 * Individual running chitties are created from a scheme but keep their own copy
 * of the financial parameters so that later edits to a scheme never mutate a
 * live chitty's terms.
 *
 * Monetary columns use decimal(15,2); floats are never used for money.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chitty_schemes', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->string('code');
            $table->string('name');
            $table->text('description')->nullable();

            $table->decimal('chit_value', 15, 2);           // total pot value
            $table->unsignedSmallInteger('duration_months'); // number of installments
            $table->unsignedSmallInteger('total_subscribers');
            $table->decimal('installment_amount', 15, 2);

            $table->decimal('foreman_commission_percent', 5, 2)->default(0);

            // monthly | fortnightly | weekly
            $table->string('auction_frequency', 20)->default('monthly');

            $table->decimal('min_bid_percent', 5, 2)->nullable(); // min discount %
            $table->decimal('max_bid_percent', 5, 2)->nullable(); // max discount %

            $table->unsignedSmallInteger('grace_period_days')->default(0);

            // none | fixed | percent
            $table->string('late_fee_type', 20)->default('none');
            $table->decimal('late_fee_value', 15, 2)->default(0);

            $table->unsignedTinyInteger('required_kyc_level')->default(1);

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
        Schema::dropIfExists('chitty_schemes');
    }
};

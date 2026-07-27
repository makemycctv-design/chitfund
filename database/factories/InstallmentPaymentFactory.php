<?php

namespace Database\Factories;

use App\Enums\InstallmentStatus;
use App\Models\Chitty;
use App\Models\ChittyMembership;
use App\Models\Company;
use App\Models\InstallmentPayment;
use App\Models\InstallmentSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InstallmentPayment>
 */
class InstallmentPaymentFactory extends Factory
{
    protected $model = InstallmentPayment::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'chitty_id' => Chitty::factory(),
            'chitty_membership_id' => ChittyMembership::factory(),
            'customer_id' => User::factory()->customer(),
            'installment_schedule_id' => InstallmentSchedule::factory(),
            'period_no' => $this->faker->numberBetween(1, 20),
            'due_date' => now()->addDays($this->faker->numberBetween(-30, 30)),
            'amount_due' => '5000.00',
            'late_fee' => '0.00',
            'amount_paid' => '0.00',
            'status' => InstallmentStatus::Pending->value,
        ];
    }
}

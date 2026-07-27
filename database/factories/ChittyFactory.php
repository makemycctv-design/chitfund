<?php

namespace Database\Factories;

use App\Enums\ChittyStatus;
use App\Models\Branch;
use App\Models\Chitty;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Chitty>
 */
class ChittyFactory extends Factory
{
    protected $model = Chitty::class;

    public function definition(): array
    {
        $chitValue = $this->faker->randomElement([100000, 200000, 500000, 1000000]);
        $duration = $this->faker->randomElement([20, 25, 40, 50]);
        $start = $this->faker->dateTimeBetween('-6 months', '+1 month');

        return [
            'ulid' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'branch_id' => Branch::factory(),
            'code' => 'CH-'.strtoupper($this->faker->unique()->bothify('???###')),
            'name' => 'Chitty '.$this->faker->words(2, true),
            'chit_value' => $chitValue,
            'duration_months' => $duration,
            'total_subscribers' => $duration,
            'installment_amount' => round($chitValue / $duration, 2),
            'foreman_commission_percent' => 5.00,
            'auction_frequency' => 'monthly',
            'min_bid_percent' => 0,
            'max_bid_percent' => 40.00,
            'grace_period_days' => 5,
            'late_fee_type' => 'percent',
            'late_fee_value' => 2.00,
            'required_kyc_level' => 1,
            'start_date' => $start,
            'maturity_date' => (clone $start)->modify("+{$duration} months"),
            'auction_day' => $this->faker->numberBetween(1, 28),
            'auction_time' => '11:00:00',
            'status' => ChittyStatus::Active->value,
        ];
    }

    public function status(ChittyStatus $status): static
    {
        return $this->state(fn () => ['status' => $status->value]);
    }
}

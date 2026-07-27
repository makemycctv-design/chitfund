<?php

namespace Database\Factories;

use App\Models\ChittyScheme;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChittyScheme>
 */
class ChittySchemeFactory extends Factory
{
    protected $model = ChittyScheme::class;

    public function definition(): array
    {
        $chitValue = $this->faker->randomElement([100000, 200000, 500000, 1000000]);
        $duration = $this->faker->randomElement([20, 25, 40, 50]);

        return [
            'ulid' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'code' => 'SCH-'.strtoupper($this->faker->unique()->bothify('???##')),
            'name' => number_format($chitValue).' / '.$duration.' months',
            'description' => $this->faker->sentence(),
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
            'is_active' => true,
        ];
    }
}

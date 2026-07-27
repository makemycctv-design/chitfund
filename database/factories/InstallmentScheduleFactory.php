<?php

namespace Database\Factories;

use App\Enums\ScheduleStatus;
use App\Models\Chitty;
use App\Models\Company;
use App\Models\InstallmentSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InstallmentSchedule>
 */
class InstallmentScheduleFactory extends Factory
{
    protected $model = InstallmentSchedule::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'chitty_id' => Chitty::factory(),
            'period_no' => $this->faker->numberBetween(1, 20),
            'due_date' => now()->addMonths($this->faker->numberBetween(0, 12)),
            'base_installment_amount' => '5000.00',
            'status' => ScheduleStatus::Pending->value,
        ];
    }
}

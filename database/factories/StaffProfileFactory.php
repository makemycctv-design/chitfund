<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StaffProfile>
 */
class StaffProfileFactory extends Factory
{
    protected $model = StaffProfile::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'user_id' => User::factory()->staff(),
            'company_id' => Company::factory(),
            'employee_code' => 'EMP-'.strtoupper($this->faker->unique()->bothify('???###')),
            'designation' => $this->faker->jobTitle(),
            'joined_on' => $this->faker->dateTimeBetween('-3 years', 'now'),
            'status' => 'active',
            'two_factor_enabled' => false,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'code' => 'BR-'.strtoupper($this->faker->unique()->bothify('???##')),
            'name' => $this->faker->city().' Branch',
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->numerify('98########'),
            'address_line1' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => 'Kerala',
            'pincode' => $this->faker->numerify('6#####'),
            'is_active' => true,
        ];
    }
}

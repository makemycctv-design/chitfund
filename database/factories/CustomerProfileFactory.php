<?php

namespace Database\Factories;

use App\Enums\KycStatus;
use App\Enums\RegistrationStatus;
use App\Models\Company;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerProfile>
 */
class CustomerProfileFactory extends Factory
{
    protected $model = CustomerProfile::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'user_id' => User::factory()->customer(),
            'company_id' => Company::factory(),
            'customer_code' => 'CUST-'.strtoupper($this->faker->unique()->bothify('???####')),
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-20 years'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'occupation' => $this->faker->jobTitle(),
            'annual_income' => $this->faker->numberBetween(200000, 2000000),
            'address_line1' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => 'Kerala',
            'pincode' => $this->faker->numerify('6#####'),
            'registration_status' => RegistrationStatus::Approved->value,
            'kyc_status' => KycStatus::Verified->value,
            'kyc_level' => 1,
            'kyc_verified_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'registration_status' => RegistrationStatus::Pending->value,
            'kyc_status' => KycStatus::Pending->value,
            'kyc_level' => 0,
            'kyc_verified_at' => null,
        ]);
    }
}

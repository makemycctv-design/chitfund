<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'ulid' => (string) Str::ulid(),
            'name' => $name,
            'legal_name' => $name.' Chit Funds Pvt. Ltd.',
            'registration_number' => 'CF-'.$this->faker->numerify('######'),
            'gstin' => $this->faker->numerify('##ABCDE####F#Z#'),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->numerify('98########'),
            'address_line1' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => 'Kerala',
            'pincode' => $this->faker->numerify('6#####'),
            'country' => 'IN',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'locale' => 'en',
            'is_active' => true,
        ];
    }
}

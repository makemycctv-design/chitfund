<?php

namespace Database\Factories;

use App\Enums\PaymentGatewayType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'customer_id' => User::factory()->customer(),
            'gateway' => PaymentGatewayType::Manual->value,
            'method' => PaymentMethod::Cash->value,
            'amount' => $this->faker->randomElement(['5000.00', '10000.00', '25000.00']),
            'currency' => 'INR',
            'status' => PaymentStatus::Success->value,
            'reference' => 'MNL-'.strtoupper(Str::random(12)),
        ];
    }

    public function online(): static
    {
        return $this->state(fn () => [
            'gateway' => PaymentGatewayType::Razorpay->value,
            'method' => PaymentMethod::Upi->value,
            'status' => PaymentStatus::Pending->value,
            'reference' => 'ONL-'.strtoupper(Str::random(12)),
        ]);
    }
}

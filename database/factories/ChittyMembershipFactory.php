<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Chitty;
use App\Models\ChittyMembership;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChittyMembership>
 */
class ChittyMembershipFactory extends Factory
{
    protected $model = ChittyMembership::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'chitty_id' => Chitty::factory(),
            'customer_id' => User::factory()->customer(),
            'ticket_number' => $this->faker->unique()->numberBetween(1, 50),
            'status' => MembershipStatus::Active->value,
            'joined_on' => now(),
            'is_prized' => false,
        ];
    }
}

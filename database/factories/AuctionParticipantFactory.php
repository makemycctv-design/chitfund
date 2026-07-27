<?php

namespace Database\Factories;

use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\ChittyMembership;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuctionParticipant>
 */
class AuctionParticipantFactory extends Factory
{
    protected $model = AuctionParticipant::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'auction_id' => Auction::factory(),
            'chitty_membership_id' => ChittyMembership::factory(),
            'customer_id' => User::factory()->customer(),
            'is_eligible' => true,
            'is_present' => true,
            'joined_at' => now(),
            'last_seen_at' => now(),
        ];
    }
}

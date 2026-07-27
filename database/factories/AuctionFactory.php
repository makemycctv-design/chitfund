<?php

namespace Database\Factories;

use App\Enums\AuctionMethod;
use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\Chitty;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Auction>
 */
class AuctionFactory extends Factory
{
    protected $model = Auction::class;

    public function definition(): array
    {
        $chitValue = 100000;

        return [
            'ulid' => (string) Str::ulid(),
            'company_id' => Company::factory(),
            'chitty_id' => Chitty::factory(),
            'period_no' => 1,
            'status' => AuctionStatus::Scheduled->value,
            'method' => AuctionMethod::MaxDiscount->value,
            'chit_value' => $chitValue,
            'foreman_commission_percent' => 5,
            'total_subscribers' => 20,
            'min_bid_amount' => 0,
            'max_bid_amount' => 40000, // up to 40% discount
            'bid_increment' => 500,
            'scheduled_at' => now(),
            'auto_extend_window_seconds' => 30,
            'auto_extend_seconds' => 30,
        ];
    }

    public function live(int $secondsLeft = 300): static
    {
        return $this->state(fn () => [
            'status' => AuctionStatus::Live->value,
            'started_at' => now(),
            'ends_at' => now()->addSeconds($secondsLeft),
        ]);
    }
}

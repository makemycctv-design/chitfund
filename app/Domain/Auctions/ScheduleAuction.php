<?php

namespace App\Domain\Auctions;

use App\Enums\AuctionMethod;
use App\Enums\AuctionStatus;
use App\Enums\MembershipStatus;
use App\Models\Auction;
use App\Models\Chitty;
use App\Models\InstallmentSchedule;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Creates a scheduled auction for a specific chitty period and snapshots its
 * financial parameters + eligible participants. Idempotent per (chitty, period)
 * via the unique DB constraint.
 */
class ScheduleAuction
{
    public function __construct(private readonly AuctionEligibility $eligibility) {}

    public function handle(Chitty $chitty, int $periodNo, CarbonInterface $scheduledAt, User $officer): Auction
    {
        return DB::transaction(function () use ($chitty, $periodNo, $scheduledAt, $officer) {
            if ($chitty->auctions()->where('period_no', $periodNo)->exists()) {
                throw new RuntimeException("An auction already exists for period {$periodNo}.");
            }

            $schedule = InstallmentSchedule::where('chitty_id', $chitty->id)
                ->where('period_no', $periodNo)
                ->first();

            // Bidding bounds derived from the chitty's discount policy.
            $chitValue = (string) $chitty->chit_value;
            $maxBid = $chitty->max_bid_percent !== null
                ? Money::percent($chitValue, (string) $chitty->max_bid_percent)
                : Money::percent($chitValue, '40');
            $minBid = $chitty->min_bid_percent !== null
                ? Money::percent($chitValue, (string) $chitty->min_bid_percent)
                : '0.00';

            $auction = Auction::create([
                'company_id' => $chitty->company_id,
                'chitty_id' => $chitty->id,
                'branch_id' => $chitty->branch_id,
                'installment_schedule_id' => $schedule?->id,
                'period_no' => $periodNo,
                'status' => AuctionStatus::Scheduled->value,
                'method' => AuctionMethod::MaxDiscount->value,
                'chit_value' => $chitValue,
                'foreman_commission_percent' => $chitty->foreman_commission_percent,
                'total_subscribers' => $chitty->total_subscribers,
                'min_bid_amount' => $minBid,
                'max_bid_amount' => $maxBid,
                'bid_increment' => '500.00',
                'scheduled_at' => $scheduledAt,
                'auto_extend_window_seconds' => 30,
                'auto_extend_seconds' => 30,
                'created_by' => $officer->id,
            ]);

            // Register every active, non-prized member with a fresh eligibility verdict.
            $members = $chitty->memberships()
                ->where('status', MembershipStatus::Active->value)
                ->where('is_prized', false)
                ->with('customer.customerProfile')
                ->get();

            foreach ($members as $membership) {
                $verdict = $this->eligibility->check($membership);
                $auction->participants()->create([
                    'company_id' => $chitty->company_id,
                    'chitty_membership_id' => $membership->id,
                    'customer_id' => $membership->customer_id,
                    'is_eligible' => $verdict['eligible'],
                    'ineligible_reason' => $verdict['reason'],
                ]);
            }

            return $auction;
        });
    }
}

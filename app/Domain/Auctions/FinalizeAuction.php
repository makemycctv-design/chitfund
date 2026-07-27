<?php

namespace App\Domain\Auctions;

use App\Enums\AuctionStatus;
use App\Enums\MembershipStatus;
use App\Enums\PayoutStatus;
use App\Enums\ScheduleStatus;
use App\Events\AuctionStateChanged;
use App\Models\Auction;
use App\Models\AuctionResult;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Closes a live/paused auction and computes the outcome ENTIRELY server-side:
 * selects the winning bid, derives prize/commission/dividend, records an
 * immutable result, flags the winning bid + membership, links the period's
 * schedule, and creates a pending prize payout. Row-locked and idempotent.
 */
class FinalizeAuction
{
    public function handle(Auction $auction, User $officer): AuctionResult
    {
        $result = DB::transaction(function () use ($auction, $officer) {
            /** @var Auction $auction */
            $auction = Auction::whereKey($auction->id)->lockForUpdate()->firstOrFail();

            if ($auction->status->isTerminal()) {
                throw new RuntimeException('Auction is already closed or cancelled.');
            }

            // Winning bid: highest discount wins (max-discount); earliest breaks ties.
            $higherWins = $auction->method->higherWins();
            $winningBid = $auction->validBids()
                ->orderBy('amount', $higherWins ? 'desc' : 'asc')
                ->orderBy('server_placed_at', 'asc')
                ->first();

            $chitValue = (string) $auction->chit_value;
            $commission = Money::percent($chitValue, (string) $auction->foreman_commission_percent);

            if ($winningBid) {
                $discount = (string) $winningBid->amount;
                $prize = Money::sub($chitValue, $discount);
                $distributable = Money::max('0.00', Money::sub($discount, $commission));
                $dividendPer = Money::divide($distributable, (string) $auction->total_subscribers);

                $winningBid->update(['is_winning' => true]);
            } else {
                $discount = '0.00';
                $prize = '0.00';
                $distributable = '0.00';
                $dividendPer = '0.00';
            }

            $auction->update([
                'status' => AuctionStatus::Closed->value,
                'winner_membership_id' => $winningBid?->chitty_membership_id,
                'winning_bid_id' => $winningBid?->id,
                'prize_amount' => $prize,
                'foreman_commission' => $commission,
                'dividend_per_member' => $dividendPer,
            ]);

            $result = AuctionResult::create([
                'company_id' => $auction->company_id,
                'auction_id' => $auction->id,
                'chitty_id' => $auction->chitty_id,
                'winner_membership_id' => $winningBid?->chitty_membership_id,
                'winner_customer_id' => $winningBid?->customer_id,
                'winning_bid_id' => $winningBid?->id,
                'discount_amount' => $discount,
                'prize_amount' => $prize,
                'foreman_commission' => $commission,
                'distributable_dividend' => $distributable,
                'dividend_per_member' => $dividendPer,
                'published_at' => now(),
            ]);

            if ($winningBid) {
                // Flag the winning membership as prized.
                $auction->winnerMembership()->update([
                    'is_prized' => true,
                    'status' => MembershipStatus::Prized->value,
                ]);

                // Link the period's schedule.
                if ($auction->installment_schedule_id) {
                    $auction->schedule()->update([
                        'auction_id' => $auction->id,
                        'status' => ScheduleStatus::Auctioned->value,
                    ]);
                }

                // Create a pending prize payout (maker-checker approval to follow).
                $primaryBank = $auction->winnerMembership->customer
                    ?->bankAccounts()->where('is_primary', true)->first();

                $result->payouts()->create([
                    'company_id' => $auction->company_id,
                    'chitty_membership_id' => $winningBid->chitty_membership_id,
                    'customer_id' => $winningBid->customer_id,
                    'amount' => $prize,
                    'status' => PayoutStatus::Pending->value,
                    'customer_bank_account_id' => $primaryBank?->id,
                ]);
            }

            return $result;
        });

        activity('auction')->performedOn($auction)->causedBy($officer)
            ->withProperties(['result' => $result->id])
            ->log('Auction finalized');

        broadcast(new AuctionStateChanged($auction->fresh(), 'closed'));

        // Notify every participant of the published result (winner flagged).
        $auction->loadMissing('participants.customer');
        foreach ($auction->participants as $participant) {
            $isWinner = $participant->chitty_membership_id === $result->winner_membership_id;
            $participant->customer?->notify(new \App\Notifications\AuctionResultPublished($result, $isWinner));
        }

        return $result;
    }
}

<?php

namespace Tests\Feature;

use App\Domain\Auctions\AuctionLifecycle;
use App\Domain\Auctions\BidRejectedException;
use App\Domain\Auctions\FinalizeAuction;
use App\Domain\Auctions\PlaceBid;
use App\Domain\Auctions\ScheduleAuction;
use App\Domain\Installments\GenerateInstallmentSchedule;
use App\Enums\AuctionStatus;
use App\Enums\MembershipStatus;
use App\Enums\PayoutStatus;
use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\Chitty;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenantData;
use Tests\TestCase;

class AuctionTest extends TestCase
{
    use BuildsTenantData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    /**
     * Build a chitty with clean bidding math + N eligible members. No schedule
     * is generated, so members have no overdue dues and are eligible.
     *
     * @return array{0: Company, 1: User, 2: Auction, 3: User[]}
     */
    private function liveAuction(int $members = 3): array
    {
        $company = $this->makeCompany();
        $officer = $this->makeStaff($company);

        $chitty = $this->makeChitty($company, [
            'chit_value' => 100000,
            'foreman_commission_percent' => 5,
            'total_subscribers' => 20,
            'installment_amount' => 5000,
            'duration_months' => 20,
            'max_bid_percent' => 40,
            'start_date' => now()->addMonth(),
        ]);

        $customers = [];
        for ($i = 1; $i <= $members; $i++) {
            $c = $this->makeCustomer($company);
            $this->enroll($chitty, $c, $i);
            $customers[] = $c;
        }

        $auction = app(ScheduleAuction::class)->handle($chitty, 1, now(), $officer);
        app(AuctionLifecycle::class)->start($auction, $officer, 600);

        return [$company, $officer, $auction->fresh(), $customers];
    }

    public function test_scheduling_registers_eligible_participants(): void
    {
        [, , $auction, $customers] = $this->liveAuction(3);

        $this->assertSame(3, $auction->participants()->count());
        $this->assertSame(3, $auction->participants()->where('is_eligible', true)->count());
        // Bounds snapshotted from the chitty's 40% discount policy.
        $this->assertSame('40000.00', (string) $auction->max_bid_amount);
    }

    public function test_valid_bid_is_accepted_and_becomes_best(): void
    {
        [, , $auction, $customers] = $this->liveAuction();

        app(PlaceBid::class)->handle($auction, $customers[0], '10000');

        $this->assertSame(1, $auction->validBids()->count());
        $this->assertSame('10000.00', (string) $auction->validBids()->first()->amount);
    }

    public function test_bid_must_beat_best_by_increment(): void
    {
        [, , $auction, $customers] = $this->liveAuction();
        app(PlaceBid::class)->handle($auction, $customers[0], '10000');

        // 10200 < 10000 + 500 increment => rejected.
        $this->expectException(BidRejectedException::class);
        app(PlaceBid::class)->handle($auction->fresh(), $customers[1], '10200');
    }

    public function test_bid_outside_bounds_is_rejected(): void
    {
        [, , $auction, $customers] = $this->liveAuction();

        $this->expectException(BidRejectedException::class);
        app(PlaceBid::class)->handle($auction, $customers[0], '50000'); // > 40000 max
    }

    public function test_bids_are_idempotent_by_key(): void
    {
        [, , $auction, $customers] = $this->liveAuction();

        $a = app(PlaceBid::class)->handle($auction, $customers[0], '10000', 'bid-key-1');
        $b = app(PlaceBid::class)->handle($auction->fresh(), $customers[0], '10000', 'bid-key-1');

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, AuctionBid::count());
    }

    public function test_bidding_is_closed_when_not_live(): void
    {
        [$company, $officer, $auction, $customers] = $this->liveAuction();
        app(AuctionLifecycle::class)->pause($auction->fresh(), $officer);

        $this->expectException(BidRejectedException::class);
        app(PlaceBid::class)->handle($auction->fresh(), $customers[0], '10000');
    }

    public function test_member_with_overdue_dues_is_ineligible(): void
    {
        $company = $this->makeCompany();
        $officer = $this->makeStaff($company);
        // Past start => period 1 overdue after schedule generation.
        $chitty = $this->makeChitty($company, ['start_date' => now()->subMonths(2), 'max_bid_percent' => 40, 'chit_value' => 100000]);
        $customer = $this->makeCustomer($company);
        $this->enroll($chitty, $customer, 1);
        app(GenerateInstallmentSchedule::class)->handle($chitty);

        $auction = app(ScheduleAuction::class)->handle($chitty, 5, now(), $officer);
        app(AuctionLifecycle::class)->start($auction, $officer, 600);

        $this->expectException(BidRejectedException::class);
        app(PlaceBid::class)->handle($auction->fresh(), $customer, '1000');
    }

    public function test_finalization_computes_winner_prize_commission_and_dividend(): void
    {
        [, $officer, $auction, $customers] = $this->liveAuction(3);

        app(PlaceBid::class)->handle($auction, $customers[0], '8000');
        app(PlaceBid::class)->handle($auction->fresh(), $customers[1], '10000'); // best (highest discount)

        $result = app(FinalizeAuction::class)->handle($auction->fresh(), $officer);

        // chit 100000, discount 10000 => prize 90000; commission 5% = 5000;
        // distributable 5000; dividend per 20 members = 250.
        $this->assertSame('10000.00', (string) $result->discount_amount);
        $this->assertSame('90000.00', (string) $result->prize_amount);
        $this->assertSame('5000.00', (string) $result->foreman_commission);
        $this->assertSame('250.00', (string) $result->dividend_per_member);

        $auction->refresh();
        $this->assertSame(AuctionStatus::Closed, $auction->status);
        $this->assertNotNull($auction->winner_membership_id);

        // Winner membership flagged as prized; a pending payout created.
        $this->assertSame(MembershipStatus::Prized, $auction->winnerMembership->fresh()->status);
        $this->assertDatabaseHas('prize_payouts', [
            'auction_result_id' => $result->id,
            'status' => PayoutStatus::Pending->value,
            'amount' => '90000.00',
        ]);
    }

    public function test_non_participant_cannot_view_or_bid_via_portal(): void
    {
        [$company, , $auction] = $this->liveAuction();
        $intruder = $this->makeCustomer($company);

        $this->actingAs($intruder)->get("/portal/auctions/{$auction->ulid}")->assertNotFound();
        $this->actingAs($intruder)->post("/portal/auctions/{$auction->ulid}/bid", ['amount' => 5000])->assertNotFound();
    }

    public function test_participant_can_bid_through_portal_endpoint(): void
    {
        [, , $auction, $customers] = $this->liveAuction();

        $this->actingAs($customers[0])
            ->post("/portal/auctions/{$auction->ulid}/bid", ['amount' => '10000'])
            ->assertRedirect();

        $this->assertSame(1, $auction->validBids()->count());
    }

    public function test_auction_pages_render(): void
    {
        [$company, $officer, $auction, $customers] = $this->liveAuction();

        $this->actingAs($officer)->get('/admin/auctions')->assertOk();
        $this->actingAs($officer)->get("/admin/auctions/{$auction->ulid}")->assertOk();
        $this->actingAs($officer)->get("/admin/auctions/{$auction->ulid}/state")->assertOk();

        $this->actingAs($customers[0])->get('/portal/auctions')->assertOk();
        $this->actingAs($customers[0])->get("/portal/auctions/{$auction->ulid}")->assertOk();
        $this->actingAs($customers[0])->get("/portal/auctions/{$auction->ulid}/state")->assertOk();
    }
}

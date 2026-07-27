<?php

namespace Tests\Feature;

use App\Domain\Auctions\AuctionLifecycle;
use App\Domain\Auctions\FinalizeAuction;
use App\Domain\Auctions\PlaceBid;
use App\Domain\Auctions\ScheduleAuction;
use App\Enums\AuctionStatus;
use App\Enums\InstallmentStatus;
use App\Enums\PayoutStatus;
use App\Models\Auction;
use App\Models\InstallmentPayment;
use App\Models\NotificationLog;
use App\Models\PrizePayout;
use App\Models\SupportTicket;
use App\Support\Rbac;
use Database\Seeders\NotificationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenantData;
use Tests\TestCase;

class Phase4Test extends TestCase
{
    use BuildsTenantData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed(NotificationTemplateSeeder::class);
    }

    public function test_notification_delivers_in_app_and_logs_and_respects_preferences(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeCustomer($company); // has email + phone

        $customer->notify(new \App\Notifications\WelcomeCustomer());

        // In-app record + delivery logs for database, mail and whatsapp.
        $this->assertSame(1, $customer->notifications()->count());
        $channels = NotificationLog::where('user_id', $customer->id)->pluck('channel')->sort()->values()->all();
        $this->assertEqualsCanonicalizing(['database', 'mail', 'whatsapp'], $channels);

        // Disable WhatsApp; the next dispatch must skip that channel.
        $customer->notificationPreferences()->create(['channel' => 'whatsapp', 'enabled' => false]);
        NotificationLog::query()->delete();

        $customer->fresh()->notify(new \App\Notifications\KycApproved());
        $after = NotificationLog::where('user_id', $customer->id)->pluck('channel')->all();
        $this->assertNotContains('whatsapp', $after);
        $this->assertContains('database', $after);
    }

    public function test_template_variables_are_rendered(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeCustomer($company);

        // Build a real successful transaction to render payment.received.
        $txn = \App\Models\PaymentTransaction::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'amount' => '5000.00',
        ]);

        $customer->notify(new \App\Notifications\PaymentReceived($txn));

        $note = $customer->notifications()->first();
        $this->assertStringContainsString('5,000.00', $note->data['message']);
    }

    public function test_send_reminders_command_notifies_due_and_overdue(): void
    {
        $company = $this->makeCompany();
        $customer = $this->makeCustomer($company);

        InstallmentPayment::factory()->create([
            'company_id' => $company->id, 'customer_id' => $customer->id,
            'due_date' => now()->addDay(), 'status' => InstallmentStatus::Pending->value, 'amount_due' => '5000.00',
        ]);
        InstallmentPayment::factory()->create([
            'company_id' => $company->id, 'customer_id' => $customer->id,
            'due_date' => now()->subDays(3), 'status' => InstallmentStatus::Pending->value, 'amount_due' => '5000.00',
        ]);

        $this->artisan('chittyfund:send-reminders')->assertSuccessful();

        $this->assertGreaterThanOrEqual(2, $customer->notifications()->count());
    }

    public function test_auto_finalize_command_closes_expired_auctions(): void
    {
        $company = $this->makeCompany();
        $officer = $this->makeStaff($company);
        $chitty = $this->makeChitty($company, ['start_date' => now()->addMonth()]);
        $this->enroll($chitty, $this->makeCustomer($company), 1);

        $auction = app(ScheduleAuction::class)->handle($chitty, 1, now(), $officer);
        app(AuctionLifecycle::class)->start($auction, $officer, 600);
        // Force the end time into the past.
        $auction->update(['ends_at' => now()->subMinute()]);

        $this->artisan('chittyfund:finalize-expired-auctions')->assertSuccessful();

        $this->assertSame(AuctionStatus::Closed, $auction->fresh()->status);
        $this->assertDatabaseHas('auction_results', ['auction_id' => $auction->id]);
    }

    public function test_prize_payout_maker_checker_flow(): void
    {
        $company = $this->makeCompany();
        $officer = $this->makeStaff($company); // company-owner: has auctions + payouts.approve
        $chitty = $this->makeChitty($company, ['start_date' => now()->addMonth(), 'chit_value' => 100000, 'max_bid_percent' => 40]);
        $bidder = $this->makeCustomer($company);
        $this->enroll($chitty, $bidder, 1);

        $auction = app(ScheduleAuction::class)->handle($chitty, 1, now(), $officer);
        app(AuctionLifecycle::class)->start($auction, $officer, 600);
        app(PlaceBid::class)->handle($auction->fresh(), $bidder, '10000');
        app(FinalizeAuction::class)->handle($auction->fresh(), $officer);

        $payout = PrizePayout::where('company_id', $company->id)->firstOrFail();
        $this->assertSame(PayoutStatus::Pending, $payout->status);

        // Cannot pay before approval.
        $this->actingAs($officer)->post("/admin/payouts/{$payout->ulid}/pay")->assertRedirect();
        $this->assertSame(PayoutStatus::Pending, $payout->fresh()->status);

        // Approve then pay.
        $this->actingAs($officer)->post("/admin/payouts/{$payout->ulid}/approve")->assertRedirect();
        $this->assertSame(PayoutStatus::Approved, $payout->fresh()->status);
        $this->actingAs($officer)->post("/admin/payouts/{$payout->ulid}/pay", ['reference' => 'NEFT123'])->assertRedirect();
        $this->assertSame(PayoutStatus::Paid, $payout->fresh()->status);
    }

    public function test_staff_can_be_created_and_suspended(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeStaff($company);

        $this->actingAs($owner)->post('/admin/staff', [
            'name' => 'New Officer', 'email' => 'newofficer@test.com', 'password' => 'password123',
            'role' => Rbac::COLLECTION_STAFF,
        ])->assertRedirect();

        $created = \App\Models\User::where('email', 'newofficer@test.com')->firstOrFail();
        $this->assertTrue($created->hasRole(Rbac::COLLECTION_STAFF));

        $this->actingAs($owner)->post("/admin/staff/{$created->ulid}/suspend")->assertRedirect();
        $this->assertFalse($created->fresh()->is_active);
    }

    public function test_support_ticket_flow_notifies_customer_on_reply(): void
    {
        $company = $this->makeCompany();
        $staff = $this->makeStaff($company);
        $customer = $this->makeCustomer($company);

        $this->actingAs($customer)->post('/portal/support', [
            'subject' => 'Cannot pay online', 'priority' => 'high', 'message' => 'The payment page errors out.',
        ])->assertRedirect();

        $ticket = SupportTicket::where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame(1, $ticket->messages()->count());

        $this->actingAs($staff)->post("/admin/support/{$ticket->ulid}/reply", ['message' => 'We are looking into it.'])->assertRedirect();

        $this->assertSame(2, $ticket->messages()->count());
        $this->assertGreaterThanOrEqual(1, $customer->notifications()->count()); // support.updated
    }

    public function test_phase4_pages_render(): void
    {
        $company = $this->makeCompany();
        $owner = $this->makeStaff($company);
        $customer = $this->makeCustomer($company);

        foreach (['/admin/staff', '/admin/staff/create', '/admin/payouts', '/admin/audit-logs',
            '/admin/notification-templates', '/admin/notification-logs', '/admin/support', '/notifications'] as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
        }

        foreach (['/portal/support', '/notifications'] as $url) {
            $this->actingAs($customer)->get($url)->assertOk();
        }
    }
}

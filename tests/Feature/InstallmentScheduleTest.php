<?php

namespace Tests\Feature;

use App\Domain\Installments\GenerateInstallmentSchedule;
use App\Models\InstallmentPayment;
use App\Models\InstallmentSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenantData;
use Tests\TestCase;

class InstallmentScheduleTest extends TestCase
{
    use BuildsTenantData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_generates_schedule_and_obligations_for_active_members(): void
    {
        $company = $this->makeCompany();
        $chitty = $this->makeChitty($company); // 3 months
        $this->enroll($chitty, $this->makeCustomer($company), 1);
        $this->enroll($chitty, $this->makeCustomer($company), 2);

        $result = app(GenerateInstallmentSchedule::class)->handle($chitty);

        $this->assertSame(3, $result['periods']);
        $this->assertSame(6, $result['obligations']); // 3 periods x 2 members
        $this->assertSame(3, InstallmentSchedule::where('chitty_id', $chitty->id)->count());
        $this->assertSame(6, InstallmentPayment::where('chitty_id', $chitty->id)->count());

        // Each obligation equals the chitty's installment amount.
        $this->assertSame('10000.00', (string) InstallmentPayment::first()->amount_due);
    }

    public function test_due_dates_advance_monthly_on_auction_day(): void
    {
        $company = $this->makeCompany();
        $chitty = $this->makeChitty($company, ['start_date' => '2026-01-01', 'auction_day' => 10]);
        $this->enroll($chitty, $this->makeCustomer($company), 1);

        app(GenerateInstallmentSchedule::class)->handle($chitty);

        $dates = InstallmentSchedule::where('chitty_id', $chitty->id)
            ->orderBy('period_no')->pluck('due_date')->map->toDateString()->all();

        $this->assertSame(['2026-01-10', '2026-02-10', '2026-03-10'], $dates);
    }

    public function test_cannot_generate_schedule_twice(): void
    {
        $company = $this->makeCompany();
        $chitty = $this->makeChitty($company);
        $this->enroll($chitty, $this->makeCustomer($company), 1);

        app(GenerateInstallmentSchedule::class)->handle($chitty);

        $this->expectException(\RuntimeException::class);
        app(GenerateInstallmentSchedule::class)->handle($chitty->fresh());
    }
}

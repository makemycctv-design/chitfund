<?php

namespace Tests\Feature;

use App\Domain\Installments\GenerateInstallmentSchedule;
use App\Domain\Payments\RecordManualPayment;
use App\Enums\InstallmentStatus;
use App\Enums\PaymentMethod;
use App\Models\InstallmentPayment;
use App\Models\PaymentReceipt;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenantData;
use Tests\TestCase;

class ManualCollectionTest extends TestCase
{
    use BuildsTenantData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function scenario(): array
    {
        $company = $this->makeCompany();
        $staff = $this->makeStaff($company);
        $customer = $this->makeCustomer($company);
        // Future start date => period 1 not yet overdue => no late fee, so a
        // ₹10,000 payment fully settles the ₹10,000 installment.
        $chitty = $this->makeChitty($company, ['start_date' => now()->addMonth()->startOfMonth()]);
        $this->enroll($chitty, $customer, 1);
        app(GenerateInstallmentSchedule::class)->handle($chitty);

        return [$company, $staff, $customer, InstallmentPayment::where('customer_id', $customer->id)->orderBy('period_no')->first()];
    }

    public function test_recording_full_payment_settles_installment_and_issues_receipt(): void
    {
        [, $staff, , $installment] = $this->scenario();

        $txn = app(RecordManualPayment::class)->handle(
            staff: $staff,
            installment: $installment,
            method: PaymentMethod::Cash,
            amount: '10000.00',
        );

        $installment->refresh();
        $this->assertSame(InstallmentStatus::Paid, $installment->status);
        $this->assertSame('10000.00', (string) $installment->amount_paid);
        $this->assertNotNull($installment->paid_at);
        $this->assertTrue($txn->isSuccessful());
        $this->assertDatabaseHas('payment_receipts', ['payment_transaction_id' => $txn->id]);
    }

    public function test_partial_payment_marks_partial(): void
    {
        [, $staff, , $installment] = $this->scenario();

        app(RecordManualPayment::class)->handle($staff, $installment, PaymentMethod::Cash, '4000.00');

        $installment->refresh();
        $this->assertSame(InstallmentStatus::Partial, $installment->status);
        $this->assertSame('4000.00', (string) $installment->amount_paid);
    }

    public function test_idempotency_key_prevents_duplicate_transactions(): void
    {
        [, $staff, , $installment] = $this->scenario();

        $a = app(RecordManualPayment::class)->handle($staff, $installment, PaymentMethod::Cash, '10000.00', null, 'key-123');
        $b = app(RecordManualPayment::class)->handle($staff, $installment, PaymentMethod::Cash, '10000.00', null, 'key-123');

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, PaymentTransaction::count());
        $this->assertSame(1, PaymentReceipt::count());
    }

    public function test_collection_endpoint_records_payment_for_staff(): void
    {
        [, $staff, , $installment] = $this->scenario();

        $this->actingAs($staff)
            ->post('/admin/collections', [
                'installment_id' => $installment->ulid,
                'method' => PaymentMethod::Cash->value,
                'amount' => '10000.00',
            ])
            ->assertRedirect();

        $this->assertSame(InstallmentStatus::Paid, $installment->fresh()->status);
    }
}

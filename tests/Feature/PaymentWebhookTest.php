<?php

namespace Tests\Feature;

use App\Domain\Installments\GenerateInstallmentSchedule;
use App\Enums\InstallmentStatus;
use App\Enums\PaymentStatus;
use App\Models\InstallmentPayment;
use App\Models\PaymentReceipt;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenantData;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use BuildsTenantData, RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        config()->set('payments.gateways.razorpay.webhook_secret', self::SECRET);
    }

    private function pendingTransaction(): PaymentTransaction
    {
        $company = $this->makeCompany();
        $customer = $this->makeCustomer($company);
        // Future start => no late fee, so the ₹10,000 payment fully settles.
        $chitty = $this->makeChitty($company, ['start_date' => now()->addMonth()->startOfMonth()]);
        $this->enroll($chitty, $customer, 1);
        app(GenerateInstallmentSchedule::class)->handle($chitty);

        $installment = InstallmentPayment::where('customer_id', $customer->id)->orderBy('period_no')->first();

        return PaymentTransaction::factory()->online()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'chitty_id' => $chitty->id,
            'amount' => '10000.00',
            'reference' => 'ONL-TESTREF01',
            'status' => PaymentStatus::Pending->value,
            'meta' => ['installment_payment_id' => $installment->id],
        ]);
    }

    private function payload(PaymentTransaction $txn, string $eventId = 'evt_1'): array
    {
        return [
            'id' => $eventId,
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_ABC123',
                'status' => 'captured',
                'amount' => 1_000_000, // paise = 10000.00
                'notes' => ['reference' => $txn->reference],
            ]]],
        ];
    }

    private function postWebhook(array $payload, string $secret): \Illuminate\Testing\TestResponse
    {
        $raw = json_encode($payload);
        $sig = hash_hmac('sha256', $raw, $secret);

        return $this->call(
            'POST',
            '/api/v1/webhooks/razorpay',
            [], [], [],
            ['HTTP_X_RAZORPAY_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'],
            $raw,
        );
    }

    public function test_valid_webhook_settles_the_installment(): void
    {
        $txn = $this->pendingTransaction();

        $this->postWebhook($this->payload($txn), self::SECRET)->assertOk();

        $txn->refresh();
        $this->assertSame(PaymentStatus::Success, $txn->status);

        $installment = InstallmentPayment::find($txn->meta['installment_payment_id']);
        $this->assertSame(InstallmentStatus::Paid, $installment->status);
        $this->assertDatabaseHas('payment_receipts', ['payment_transaction_id' => $txn->id]);
    }

    public function test_duplicate_webhook_is_processed_once(): void
    {
        $txn = $this->pendingTransaction();
        $payload = $this->payload($txn, 'evt_dup');

        $this->postWebhook($payload, self::SECRET)->assertOk();
        $this->postWebhook($payload, self::SECRET)->assertOk();

        $this->assertSame(1, PaymentReceipt::where('payment_transaction_id', $txn->id)->count());
        $this->assertSame(1, \App\Models\PaymentWebhookEvent::where('event_id', 'evt_dup')->count());
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $txn = $this->pendingTransaction();

        $this->postWebhook($this->payload($txn), 'wrong-secret')->assertStatus(400);

        $this->assertSame(PaymentStatus::Pending, $txn->fresh()->status);
    }
}

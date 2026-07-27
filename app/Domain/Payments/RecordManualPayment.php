<?php

namespace App\Domain\Payments;

use App\Enums\PaymentGatewayType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\InstallmentPayment;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Records an offline collection (cash / bank transfer / counter UPI) made by a
 * staff member against a specific installment. Creates a successful manual
 * transaction, settles the installment, and issues a receipt — all atomically.
 * An optional idempotency key prevents accidental double submission.
 */
class RecordManualPayment
{
    public function __construct(
        private readonly SettlePayment $settlePayment,
        private readonly GenerateReceipt $generateReceipt,
    ) {}

    public function handle(
        User $staff,
        InstallmentPayment $installment,
        PaymentMethod $method,
        string $amount,
        ?string $note = null,
        ?string $idempotencyKey = null,
    ): PaymentTransaction {
        return DB::transaction(function () use ($staff, $installment, $method, $amount, $note, $idempotencyKey) {
            if ($idempotencyKey) {
                $existing = PaymentTransaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }
            }

            $transaction = PaymentTransaction::create([
                'company_id' => $installment->company_id,
                'customer_id' => $installment->customer_id,
                'chitty_id' => $installment->chitty_id,
                'gateway' => PaymentGatewayType::Manual->value,
                'method' => $method->value,
                'amount' => Money::of($amount),
                'currency' => 'INR',
                'status' => PaymentStatus::Success->value,
                'reference' => 'MNL-'.strtoupper(Str::random(12)),
                'idempotency_key' => $idempotencyKey,
                'recorded_by' => $staff->id,
                'approved_by' => $staff->id,
                'reconciled_at' => now(),
                'meta' => [
                    'installment_payment_id' => $installment->id,
                    'note' => $note,
                ],
            ]);

            $this->settlePayment->settle($transaction, $installment);
            $this->generateReceipt->forTransaction($transaction);

            activity('payment')
                ->performedOn($transaction)
                ->causedBy($staff)
                ->withProperties(['installment' => $installment->id, 'amount' => $transaction->amount])
                ->log('Manual payment recorded');

            return $transaction;
        });
    }
}

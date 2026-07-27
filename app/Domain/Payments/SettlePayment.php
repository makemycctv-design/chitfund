<?php

namespace App\Domain\Payments;

use App\Domain\Installments\LateFeeCalculator;
use App\Enums\InstallmentStatus;
use App\Models\InstallmentPayment;
use App\Models\PaymentTransaction;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Applies a successful payment transaction to a single installment obligation.
 * Row-locks the installment, (re)computes any late fee as of settlement time,
 * updates the paid amount and status, and is idempotent for already-paid rows.
 */
class SettlePayment
{
    public function __construct(private readonly LateFeeCalculator $lateFees) {}

    public function settle(PaymentTransaction $transaction, InstallmentPayment $installment): InstallmentPayment
    {
        return DB::transaction(function () use ($transaction, $installment) {
            /** @var InstallmentPayment $installment */
            $installment = InstallmentPayment::whereKey($installment->id)->lockForUpdate()->firstOrFail();

            // Already settled by an earlier (possibly retried) call.
            if ($installment->status === InstallmentStatus::Paid) {
                return $installment;
            }

            $chitty = $installment->chitty()->first();

            // Recompute late fee at settlement time (unless waived).
            $lateFee = $this->lateFees->calculate(
                $chitty,
                (string) $installment->amount_due,
                $installment->due_date,
                now(),
            );

            $totalPayable = Money::add((string) $installment->amount_due, $lateFee);
            $newPaid = Money::add((string) $installment->amount_paid, (string) $transaction->amount);

            $fullyPaid = Money::compare($newPaid, $totalPayable) >= 0;

            $installment->late_fee = $lateFee;
            $installment->amount_paid = Money::min($newPaid, $totalPayable);
            $installment->status = $fullyPaid ? InstallmentStatus::Paid->value : InstallmentStatus::Partial->value;
            $installment->paid_at = $fullyPaid ? now() : $installment->paid_at;
            $installment->payment_transaction_id = $transaction->id;
            $installment->save();

            return $installment;
        });
    }
}

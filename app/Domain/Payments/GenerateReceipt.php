<?php

namespace App\Domain\Payments;

use App\Models\PaymentReceipt;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Issues a receipt for a successful transaction. Idempotent — a transaction can
 * only ever have one receipt (enforced by a unique column too).
 */
class GenerateReceipt
{
    public function __construct(private readonly ReceiptNumberGenerator $numbers) {}

    public function forTransaction(PaymentTransaction $transaction): PaymentReceipt
    {
        return DB::transaction(function () use ($transaction) {
            if ($existing = $transaction->receipt()->first()) {
                return $existing;
            }

            return PaymentReceipt::create([
                'company_id' => $transaction->company_id,
                'customer_id' => $transaction->customer_id,
                'payment_transaction_id' => $transaction->id,
                'receipt_number' => $this->numbers->next($transaction->company_id),
                'amount' => $transaction->amount,
                'issued_at' => now(),
            ]);
        });
    }
}

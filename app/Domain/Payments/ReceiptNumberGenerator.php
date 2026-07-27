<?php

namespace App\Domain\Payments;

use App\Models\PaymentReceipt;
use Illuminate\Support\Facades\DB;

/**
 * Produces human-friendly, per-company sequential receipt numbers of the form
 * RCPT-{YYYY}-{000123}. Meant to be called inside the same transaction that
 * creates the receipt so the sequence has no gaps/duplicates.
 */
class ReceiptNumberGenerator
{
    public function next(int $companyId): string
    {
        $year = now()->year;
        $prefix = "RCPT-{$year}-";

        // Count existing receipts for this company/year to derive the sequence.
        $count = PaymentReceipt::where('company_id', $companyId)
            ->where('receipt_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->count();

        return $prefix.str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
    }
}

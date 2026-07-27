<?php

namespace App\Domain\Installments;

use App\Models\Chitty;
use App\Support\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Computes the late fee owed on an installment. Rules are read from the chitty
 * (type: none|fixed|percent, value, grace_period_days) so they stay
 * configurable per company/state. Returns a money string (2 dp).
 */
class LateFeeCalculator
{
    /**
     * @param  string  $amountDue      Base installment amount (money string)
     * @param  CarbonInterface  $dueDate  When the installment was due
     * @param  CarbonInterface|null  $asOf  Evaluation date (defaults to now)
     */
    public function calculate(Chitty $chitty, string $amountDue, CarbonInterface $dueDate, ?CarbonInterface $asOf = null): string
    {
        $asOf = $asOf ?? Carbon::now();

        $graceEnd = (clone $dueDate)->addDays($chitty->grace_period_days);

        // Within grace period (or not yet due) => no late fee.
        if ($asOf->lessThanOrEqualTo($graceEnd)) {
            return '0.00';
        }

        return match ($chitty->late_fee_type) {
            'fixed' => Money::of((string) $chitty->late_fee_value),
            'percent' => Money::percent($amountDue, (string) $chitty->late_fee_value),
            default => '0.00', // 'none'
        };
    }
}

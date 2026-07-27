<?php

namespace App\Domain\Installments;

use App\Enums\InstallmentStatus;
use App\Enums\MembershipStatus;
use App\Enums\ScheduleStatus;
use App\Models\Chitty;
use App\Models\InstallmentPayment;
use App\Models\InstallmentSchedule;
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Generates a chitty's full installment schedule: one InstallmentSchedule row
 * per period, and one InstallmentPayment obligation per active membership per
 * period. Runs inside a transaction and is guarded against double-generation.
 */
class GenerateInstallmentSchedule
{
    /**
     * @return array{periods:int, obligations:int}
     */
    public function handle(Chitty $chitty): array
    {
        if (! $chitty->start_date) {
            throw new RuntimeException('Chitty must have a start date before generating a schedule.');
        }

        return DB::transaction(function () use ($chitty) {
            // Lock the row to prevent concurrent generation.
            $chitty = Chitty::whereKey($chitty->id)->lockForUpdate()->firstOrFail();

            if ($chitty->schedules()->exists()) {
                throw new RuntimeException('An installment schedule already exists for this chitty.');
            }

            $activeMemberships = $chitty->memberships()
                ->where('status', MembershipStatus::Active->value)
                ->get();

            $baseAmount = Money::of((string) $chitty->installment_amount);
            $periods = 0;
            $obligations = 0;

            for ($period = 1; $period <= $chitty->duration_months; $period++) {
                $dueDate = $this->dueDateFor($chitty, $period);

                $schedule = InstallmentSchedule::create([
                    'company_id' => $chitty->company_id,
                    'chitty_id' => $chitty->id,
                    'period_no' => $period,
                    'due_date' => $dueDate,
                    'base_installment_amount' => $baseAmount,
                    'status' => ScheduleStatus::Pending->value,
                ]);
                $periods++;

                foreach ($activeMemberships as $membership) {
                    InstallmentPayment::create([
                        'company_id' => $chitty->company_id,
                        'chitty_id' => $chitty->id,
                        'chitty_membership_id' => $membership->id,
                        'customer_id' => $membership->customer_id,
                        'installment_schedule_id' => $schedule->id,
                        'period_no' => $period,
                        'due_date' => $dueDate,
                        'amount_due' => $baseAmount,
                        'late_fee' => '0.00',
                        'amount_paid' => '0.00',
                        'status' => InstallmentStatus::Pending->value,
                    ]);
                    $obligations++;
                }
            }

            return ['periods' => $periods, 'obligations' => $obligations];
        });
    }

    /**
     * Due date for a period: start month + (period-1), placed on the chitty's
     * configured auction day (clamped to the month length) when set.
     */
    private function dueDateFor(Chitty $chitty, int $period): Carbon
    {
        $date = Carbon::parse($chitty->start_date)->startOfDay()->addMonthsNoOverflow($period - 1);

        if ($chitty->auction_day) {
            $day = min($chitty->auction_day, $date->daysInMonth);
            $date = $date->day($day);
        }

        return $date;
    }
}

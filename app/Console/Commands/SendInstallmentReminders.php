<?php

namespace App\Console\Commands;

use App\Models\InstallmentPayment;
use App\Notifications\InstallmentDueReminder;
use Illuminate\Console\Command;

/**
 * Sends "due soon" reminders for installments due within a window, and
 * "overdue" reminders for past-due obligations. Run daily by the scheduler.
 */
class SendInstallmentReminders extends Command
{
    protected $signature = 'chittyfund:send-reminders {--days=3 : Days ahead to remind}';

    protected $description = 'Send installment due-soon and overdue reminders';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dueSoon = 0;
        $overdue = 0;

        InstallmentPayment::query()
            ->outstanding()
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->with('customer', 'chitty:id,code')
            ->chunkById(200, function ($installments) use (&$dueSoon) {
                foreach ($installments as $installment) {
                    $installment->customer?->notify(new InstallmentDueReminder($installment, overdue: false));
                    $dueSoon++;
                }
            });

        InstallmentPayment::query()
            ->overdue()
            ->with('customer', 'chitty:id,code')
            ->chunkById(200, function ($installments) use (&$overdue) {
                foreach ($installments as $installment) {
                    $installment->customer?->notify(new InstallmentDueReminder($installment, overdue: true));
                    $overdue++;
                }
            });

        $this->info("Reminders queued — due soon: {$dueSoon}, overdue: {$overdue}.");

        return self::SUCCESS;
    }
}

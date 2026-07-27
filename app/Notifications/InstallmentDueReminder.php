<?php

namespace App\Notifications;

use App\Models\InstallmentPayment;

class InstallmentDueReminder extends ChittyNotification
{
    public function __construct(public InstallmentPayment $installment, public bool $overdue = false) {}

    public function eventKey(): string
    {
        return $this->overdue ? 'installment.overdue' : 'installment.due_reminder';
    }

    protected function title(): string
    {
        return $this->overdue ? 'Installment Overdue' : 'Installment Due Soon';
    }

    protected function variables(object $notifiable): array
    {
        return [
            'name' => $notifiable->name,
            'amount' => number_format((float) $this->installment->outstanding(), 2),
            'due_date' => $this->installment->due_date?->format('d M Y') ?? '',
            'chitty' => $this->installment->chitty?->code ?? '',
        ];
    }

    protected function actionUrl(object $notifiable): ?string
    {
        return '/portal/chitties';
    }
}

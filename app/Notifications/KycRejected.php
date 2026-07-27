<?php

namespace App\Notifications;

class KycRejected extends ChittyNotification
{
    public function __construct(public string $reason) {}

    public function eventKey(): string
    {
        return 'kyc.rejected';
    }

    protected function title(): string
    {
        return 'KYC Needs Attention';
    }

    protected function variables(object $notifiable): array
    {
        return ['name' => $notifiable->name, 'reason' => $this->reason];
    }

    protected function actionUrl(object $notifiable): ?string
    {
        return '/portal/profile';
    }
}

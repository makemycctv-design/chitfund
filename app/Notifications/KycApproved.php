<?php

namespace App\Notifications;

class KycApproved extends ChittyNotification
{
    public function eventKey(): string
    {
        return 'kyc.approved';
    }

    protected function title(): string
    {
        return 'KYC Verified';
    }

    protected function variables(object $notifiable): array
    {
        return ['name' => $notifiable->name];
    }

    protected function actionUrl(object $notifiable): ?string
    {
        return '/portal/profile';
    }
}

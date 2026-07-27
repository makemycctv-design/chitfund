<?php

namespace App\Notifications;

class WelcomeCustomer extends ChittyNotification
{
    public function eventKey(): string
    {
        return 'welcome';
    }

    protected function title(): string
    {
        return 'Welcome to ChittyFund';
    }

    protected function variables(object $notifiable): array
    {
        return [
            'name' => $notifiable->name,
            'company' => $notifiable->company?->name ?? 'ChittyFund',
        ];
    }

    protected function actionUrl(object $notifiable): ?string
    {
        return '/portal/dashboard';
    }
}

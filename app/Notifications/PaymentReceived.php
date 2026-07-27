<?php

namespace App\Notifications;

use App\Models\PaymentTransaction;

class PaymentReceived extends ChittyNotification
{
    public function __construct(public PaymentTransaction $transaction) {}

    public function eventKey(): string
    {
        return 'payment.received';
    }

    protected function title(): string
    {
        return 'Payment Received';
    }

    protected function variables(object $notifiable): array
    {
        return [
            'name' => $notifiable->name,
            'amount' => number_format((float) $this->transaction->amount, 2),
            'reference' => $this->transaction->reference,
            'receipt_number' => $this->transaction->receipt?->receipt_number ?? '',
            'chitty' => $this->transaction->chitty?->code ?? '',
        ];
    }

    protected function actionUrl(object $notifiable): ?string
    {
        return '/portal/payments';
    }
}

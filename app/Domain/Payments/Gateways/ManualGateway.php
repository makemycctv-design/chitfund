<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Models\PaymentTransaction;

/**
 * Offline collections (cash / bank transfer recorded by staff). There is no
 * external provider, so order creation is a no-op and there are no webhooks.
 */
class ManualGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'manual';
    }

    public function createOrder(PaymentTransaction $transaction): array
    {
        return ['gateway' => 'manual', 'reference' => $transaction->reference];
    }

    public function verifySignature(string $payload, string $signature): bool
    {
        return false; // manual gateway never receives webhooks
    }

    public function parseWebhook(array $payload): array
    {
        return [
            'event_id' => '',
            'type' => '',
            'status' => 'failed',
            'gateway_payment_id' => null,
            'reference' => null,
            'amount' => null,
        ];
    }
}

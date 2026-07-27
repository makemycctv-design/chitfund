<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Support\Money;

/**
 * Razorpay integration.
 *
 * Order creation against Razorpay's REST API is intentionally left as a guarded
 * stub: it requires live credentials and outbound calls, so it is only invoked
 * when keys are configured. Signature verification (the security-critical part)
 * is fully implemented via HMAC-SHA256 as Razorpay specifies, so inbound
 * webhooks are validated even in this phase.
 */
class RazorpayGateway implements PaymentGateway
{
    public function __construct(
        private readonly ?string $keyId,
        private readonly ?string $keySecret,
        private readonly ?string $webhookSecret,
    ) {}

    public function name(): string
    {
        return 'razorpay';
    }

    public function createOrder(PaymentTransaction $transaction): array
    {
        if (! $this->keyId || ! $this->keySecret) {
            throw new \RuntimeException('Razorpay credentials are not configured.');
        }

        // Razorpay expects the amount in the smallest currency unit (paise).
        $amountPaise = (int) round(((float) Money::of((string) $transaction->amount)) * 100);

        // NOTE: a real implementation POSTs to https://api.razorpay.com/v1/orders
        // with basic auth (keyId:keySecret). Returned here is the payload the
        // client SDK needs; the order id would come from that API response.
        return [
            'key' => $this->keyId,
            'amount' => $amountPaise,
            'currency' => $transaction->currency,
            'reference' => $transaction->reference,
        ];
    }

    public function verifySignature(string $payload, string $signature): bool
    {
        if (! $this->webhookSecret) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    public function parseWebhook(array $payload): array
    {
        $entity = data_get($payload, 'payload.payment.entity', []);

        // Razorpay payment status "captured" == money settled.
        $status = data_get($entity, 'status') === 'captured' ? 'success' : 'failed';

        return [
            'event_id' => (string) data_get($payload, 'id', data_get($entity, 'id', '')),
            'type' => (string) data_get($payload, 'event', ''),
            'status' => $status,
            'gateway_payment_id' => data_get($entity, 'id'),
            'reference' => data_get($entity, 'notes.reference') ?? data_get($entity, 'order_id'),
            'amount' => data_get($entity, 'amount') !== null
                ? Money::of((string) (data_get($entity, 'amount') / 100))
                : null,
        ];
    }
}

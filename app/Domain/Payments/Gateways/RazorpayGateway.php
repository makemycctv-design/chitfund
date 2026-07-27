<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Models\PaymentTransaction;
use App\Support\Money;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Razorpay integration.
 *
 * `createOrder` calls Razorpay's Orders API (HTTP basic auth with the key
 * id/secret) to obtain a real order id the client Checkout needs. Both webhook
 * signatures (HMAC of the raw body with the webhook secret) and client
 * checkout signatures (HMAC of "order_id|payment_id" with the key secret) are
 * verified server-side, exactly as Razorpay specifies.
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

        $response = Http::withBasicAuth($this->keyId, $this->keySecret)
            ->acceptJson()
            ->asJson()
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountPaise,
                'currency' => $transaction->currency,
                'receipt' => $transaction->reference,
                'notes' => ['reference' => $transaction->reference],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Razorpay order creation failed: '.$response->body());
        }

        return [
            'order_id' => (string) data_get($response->json(), 'id'),
            'key' => $this->keyId,
            'amount' => $amountPaise,
            'currency' => $transaction->currency,
            'reference' => $transaction->reference,
        ];
    }

    /**
     * Verify the signature returned to the browser by Razorpay Checkout.
     * Razorpay signs "razorpay_order_id|razorpay_payment_id" with the key
     * secret (HMAC-SHA256).
     */
    public function verifyCheckoutSignature(string $orderId, string $paymentId, string $signature): bool
    {
        if (! $this->keySecret) {
            return false;
        }

        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $this->keySecret);

        return hash_equals($expected, $signature);
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

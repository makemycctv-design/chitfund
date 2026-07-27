<?php

namespace App\Domain\Payments\Contracts;

use App\Models\PaymentTransaction;

/**
 * Contract every payment provider implements. Keeping the app coded against
 * this interface means Razorpay/Cashfree/Stripe/manual are interchangeable and
 * secrets never leak into controllers or the client.
 */
interface PaymentGateway
{
    public function name(): string;

    /**
     * Create/initialize an order at the provider for the given transaction.
     * Returns provider data the client needs to complete payment (order id,
     * public key, etc.). For the manual gateway this is a no-op.
     *
     * @return array<string, mixed>
     */
    public function createOrder(PaymentTransaction $transaction): array;

    /**
     * Verify a webhook/callback signature against the raw request body.
     */
    public function verifySignature(string $payload, string $signature): bool;

    /**
     * Verify a client-side checkout signature (returned to the browser after
     * the customer completes payment). Providers without a browser checkout
     * flow return false.
     */
    public function verifyCheckoutSignature(string $orderId, string $paymentId, string $signature): bool;

    /**
     * Normalize a raw webhook payload into a provider-agnostic shape.
     *
     * @param  array<string, mixed>  $payload
     * @return array{event_id:string, type:string, status:string, gateway_payment_id:?string, reference:?string, amount:?string}
     */
    public function parseWebhook(array $payload): array;
}

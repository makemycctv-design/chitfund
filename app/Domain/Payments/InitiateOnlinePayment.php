<?php

namespace App\Domain\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\InstallmentPayment;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Starts an online payment for an installment. Creates an `initiated`
 * transaction, asks the gateway to create an order, and returns the client
 * payload. The payment is only ever marked successful by the verified webhook —
 * never by a client redirect. The target installment id is stored in meta so
 * the webhook knows what to settle.
 */
class InitiateOnlinePayment
{
    public function __construct(private readonly PaymentGatewayManager $gateways) {}

    /**
     * @return array{transaction: \App\Models\PaymentTransaction, gateway: array<string,mixed>}
     */
    public function handle(InstallmentPayment $installment, PaymentMethod $method, ?string $gatewayName = null): array
    {
        $gateway = $this->gateways->gateway($gatewayName);

        return DB::transaction(function () use ($installment, $method, $gateway) {
            $outstanding = $installment->outstanding();

            $transaction = $installment->customer->paymentTransactions()->create([
                'company_id' => $installment->company_id,
                'chitty_id' => $installment->chitty_id,
                'gateway' => $gateway->name(),
                'method' => $method->value,
                'amount' => Money::isZero($outstanding) ? (string) $installment->amount_due : $outstanding,
                'currency' => 'INR',
                'status' => PaymentStatus::Initiated->value,
                'reference' => 'ONL-'.strtoupper(Str::random(12)),
                'meta' => ['installment_payment_id' => $installment->id],
            ]);

            $order = $gateway->createOrder($transaction);

            $transaction->update([
                'gateway_order_id' => $order['order_id'] ?? ($order['reference'] ?? null),
                'status' => PaymentStatus::Pending->value,
            ]);

            return ['transaction' => $transaction, 'gateway' => $order];
        });
    }
}

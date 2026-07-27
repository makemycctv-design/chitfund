<?php

namespace App\Domain\Payments;

use App\Enums\PaymentStatus;
use App\Models\InstallmentPayment;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookEvent;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Verifies and processes an inbound gateway webhook. Security rules:
 *  - the signature is verified against the RAW body before anything else;
 *  - each (gateway, event_id) is stored once and processed once (idempotent);
 *  - the transaction is only marked successful here (server-to-server),
 *    never by a client redirect.
 */
class ProcessGatewayWebhook
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly SettlePayment $settlePayment,
        private readonly GenerateReceipt $generateReceipt,
    ) {}

    public function handle(string $gatewayName, string $rawPayload, string $signature): PaymentWebhookEvent
    {
        $gateway = $this->gateways->gateway($gatewayName);

        if (! $gateway->verifySignature($rawPayload, $signature)) {
            throw new RuntimeException('Invalid webhook signature.');
        }

        $payload = json_decode($rawPayload, true) ?? [];
        $event = $gateway->parseWebhook($payload);

        return DB::transaction(function () use ($gatewayName, $event, $payload) {
            // Idempotency guard: unique (gateway, event_id).
            $record = PaymentWebhookEvent::firstOrCreate(
                ['gateway' => $gatewayName, 'event_id' => $event['event_id']],
                ['type' => $event['type'], 'payload' => $payload, 'status' => 'received'],
            );

            if ($record->status === 'processed') {
                return $record; // already handled a redelivery
            }

            $transaction = $this->locateTransaction($event);

            if (! $transaction) {
                $record->update(['status' => 'ignored', 'error' => 'No matching transaction', 'processed_at' => now()]);

                return $record;
            }

            $record->payment_transaction_id = $transaction->id;

            if ($event['status'] === 'success') {
                $this->markSuccessful($transaction, $event);
                $record->status = 'processed';
            } else {
                $transaction->update([
                    'status' => PaymentStatus::Failed->value,
                    'failure_reason' => 'Gateway reported failure',
                ]);
                $record->status = 'processed';
            }

            $record->processed_at = now();
            $record->save();

            return $record;
        });
    }

    private function locateTransaction(array $event): ?PaymentTransaction
    {
        $query = PaymentTransaction::query();

        if (! empty($event['reference'])) {
            $found = (clone $query)->where('reference', $event['reference'])
                ->orWhere('gateway_order_id', $event['reference'])->first();
            if ($found) {
                return $found;
            }
        }

        if (! empty($event['gateway_payment_id'])) {
            return $query->where('gateway_payment_id', $event['gateway_payment_id'])->first();
        }

        return null;
    }

    private function markSuccessful(PaymentTransaction $transaction, array $event): void
    {
        // Idempotent: skip if already settled.
        if ($transaction->status === PaymentStatus::Success) {
            return;
        }

        $transaction->update([
            'status' => PaymentStatus::Success->value,
            'gateway_payment_id' => $event['gateway_payment_id'] ?? $transaction->gateway_payment_id,
            'reconciled_at' => now(),
        ]);

        $installmentId = data_get($transaction->meta, 'installment_payment_id');
        if ($installmentId && ($installment = InstallmentPayment::find($installmentId))) {
            $this->settlePayment->settle($transaction, $installment);
        }

        $this->generateReceipt->forTransaction($transaction);

        // Notify the customer that their online payment succeeded.
        $transaction->customer?->notify(new \App\Notifications\PaymentReceived($transaction->fresh()->load('receipt', 'chitty')));
    }
}

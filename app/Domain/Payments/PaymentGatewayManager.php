<?php

namespace App\Domain\Payments;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Gateways\ManualGateway;
use App\Domain\Payments\Gateways\RazorpayGateway;
use InvalidArgumentException;

/**
 * Resolves a PaymentGateway implementation by name from config/payments.php.
 * Only implemented drivers are wired here; unimplemented ones fail loudly.
 */
class PaymentGatewayManager
{
    /** @var array<string, PaymentGateway> */
    private array $resolved = [];

    public function gateway(?string $name = null): PaymentGateway
    {
        $name = $name ?: config('payments.default');

        return $this->resolved[$name] ??= $this->make($name);
    }

    private function make(string $name): PaymentGateway
    {
        $config = config("payments.gateways.{$name}");

        if (! $config) {
            throw new InvalidArgumentException("Payment gateway [{$name}] is not configured.");
        }

        return match ($config['driver']) {
            'manual' => new ManualGateway(),
            'razorpay' => new RazorpayGateway(
                $config['key_id'] ?? null,
                $config['key_secret'] ?? null,
                $config['webhook_secret'] ?? null,
            ),
            default => throw new InvalidArgumentException(
                "Payment gateway driver [{$config['driver']}] is not implemented yet."
            ),
        };
    }
}

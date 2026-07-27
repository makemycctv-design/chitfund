<?php

namespace App\Enums;

enum PaymentGatewayType: string
{
    case Manual = 'manual';
    case Razorpay = 'razorpay';
    case Cashfree = 'cashfree';
    case PhonePe = 'phonepe';
    case Stripe = 'stripe';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual / Offline',
            self::Razorpay => 'Razorpay',
            self::Cashfree => 'Cashfree',
            self::PhonePe => 'PhonePe',
            self::Stripe => 'Stripe',
        };
    }
}

<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Upi = 'upi';
    case Card = 'card';
    case NetBanking = 'netbanking';
    case Wallet = 'wallet';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Upi => 'UPI',
            self::Card => 'Card',
            self::NetBanking => 'Net Banking',
            self::Wallet => 'Wallet',
            self::BankTransfer => 'Bank Transfer',
        };
    }

    /** Methods a staff member can record manually (offline collections). */
    public static function manualMethods(): array
    {
        return [self::Cash->value, self::BankTransfer->value, self::Upi->value];
    }
}

<?php

namespace App\Enums;

enum KycDocumentType: string
{
    case Aadhaar = 'aadhaar';
    case Pan = 'pan';
    case Photo = 'photo';
    case AddressProof = 'address_proof';
    case BankProof = 'bank_proof';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Aadhaar => 'Aadhaar',
            self::Pan => 'PAN',
            self::Photo => 'Photograph',
            self::AddressProof => 'Address Proof',
            self::BankProof => 'Bank Proof',
            self::Other => 'Other',
        };
    }
}

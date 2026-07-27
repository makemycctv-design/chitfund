<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReceipt extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentReceiptFactory> */
    use BelongsToCompany, HasFactory, HasUlid;

    protected $fillable = [
        'company_id', 'customer_id', 'payment_transaction_id',
        'receipt_number', 'amount', 'issued_at', 'pdf_path', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}

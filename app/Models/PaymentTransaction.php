<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentTransactionFactory> */
    use BelongsToCompany, HasFactory, HasUlid;

    protected $fillable = [
        'company_id', 'customer_id', 'chitty_id', 'gateway', 'method',
        'amount', 'currency', 'status', 'reference', 'idempotency_key',
        'gateway_order_id', 'gateway_payment_id', 'gateway_signature',
        'recorded_by', 'approved_by', 'reconciled_at', 'failure_reason', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
            'method' => PaymentMethod::class,
            'reconciled_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function chitty(): BelongsTo
    {
        return $this->belongsTo(Chitty::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function installmentPayments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class, 'payment_transaction_id');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(PaymentReceipt::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Success;
    }
}

<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallmentPayment extends Model
{
    /** @use HasFactory<\Database\Factories\InstallmentPaymentFactory> */
    use BelongsToCompany, HasFactory, HasUlid, SoftDeletes;

    protected $fillable = [
        'company_id', 'chitty_id', 'chitty_membership_id', 'customer_id',
        'installment_schedule_id', 'period_no', 'due_date',
        'amount_due', 'late_fee', 'amount_paid', 'status', 'paid_at',
        'payment_transaction_id', 'waived_by', 'waiver_reason', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'period_no' => 'integer',
            'due_date' => 'date',
            'amount_due' => 'decimal:2',
            'late_fee' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'status' => InstallmentStatus::class,
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function chitty(): BelongsTo
    {
        return $this->belongsTo(Chitty::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(ChittyMembership::class, 'chitty_membership_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(InstallmentSchedule::class, 'installment_schedule_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    /** Total payable = amount due + accrued late fee. */
    public function totalPayable(): string
    {
        return Money::add((string) $this->amount_due, (string) $this->late_fee);
    }

    /** Outstanding = total payable - amount paid (never negative). */
    public function outstanding(): string
    {
        $out = Money::sub($this->totalPayable(), (string) $this->amount_paid);

        return Money::isNegative($out) ? '0.00' : $out;
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', InstallmentStatus::outstanding());
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->outstanding()->whereDate('due_date', '<', now());
    }
}

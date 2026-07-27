<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrizePayout extends Model
{
    /** @use HasFactory<\Database\Factories\PrizePayoutFactory> */
    use BelongsToCompany, HasFactory, HasUlid;

    protected $fillable = [
        'company_id', 'auction_result_id', 'chitty_membership_id', 'customer_id',
        'amount', 'status', 'customer_bank_account_id', 'approved_by',
        'approved_at', 'paid_at', 'reference', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PayoutStatus::class,
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(AuctionResult::class, 'auction_result_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}

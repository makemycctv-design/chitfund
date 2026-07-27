<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionBid extends Model
{
    /** @use HasFactory<\Database\Factories\AuctionBidFactory> */
    use BelongsToCompany, HasFactory, HasUlid;

    protected $fillable = [
        'company_id', 'auction_id', 'auction_participant_id', 'chitty_membership_id',
        'customer_id', 'amount', 'is_valid', 'is_winning', 'rejection_reason',
        'idempotency_key', 'server_placed_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_valid' => 'boolean',
            'is_winning' => 'boolean',
            'server_placed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(ChittyMembership::class, 'chitty_membership_id');
    }
}

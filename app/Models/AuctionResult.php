<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuctionResult extends Model
{
    /** @use HasFactory<\Database\Factories\AuctionResultFactory> */
    use BelongsToCompany, HasFactory, HasUlid;

    protected $fillable = [
        'company_id', 'auction_id', 'chitty_id', 'winner_membership_id', 'winner_customer_id',
        'winning_bid_id', 'discount_amount', 'prize_amount', 'foreman_commission',
        'distributable_dividend', 'dividend_per_member', 'published_at', 'report_path', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'prize_amount' => 'decimal:2',
            'foreman_commission' => 'decimal:2',
            'distributable_dividend' => 'decimal:2',
            'dividend_per_member' => 'decimal:2',
            'published_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function chitty(): BelongsTo
    {
        return $this->belongsTo(Chitty::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_customer_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(PrizePayout::class);
    }
}

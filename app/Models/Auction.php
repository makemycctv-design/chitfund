<?php

namespace App\Models;

use App\Enums\AuctionMethod;
use App\Enums\AuctionStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Auction extends Model
{
    /** @use HasFactory<\Database\Factories\AuctionFactory> */
    use BelongsToCompany, HasFactory, HasUlid;

    protected $fillable = [
        'company_id', 'chitty_id', 'branch_id', 'installment_schedule_id', 'period_no',
        'status', 'method', 'chit_value', 'foreman_commission_percent', 'total_subscribers',
        'min_bid_amount', 'max_bid_amount', 'bid_increment',
        'scheduled_at', 'started_at', 'ends_at', 'paused_at',
        'auto_extend_window_seconds', 'auto_extend_seconds', 'extended_count',
        'winner_membership_id', 'winning_bid_id', 'prize_amount', 'foreman_commission',
        'dividend_per_member', 'cancelled_reason', 'created_by', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => AuctionStatus::class,
            'method' => AuctionMethod::class,
            'chit_value' => 'decimal:2',
            'foreman_commission_percent' => 'decimal:2',
            'min_bid_amount' => 'decimal:2',
            'max_bid_amount' => 'decimal:2',
            'bid_increment' => 'decimal:2',
            'prize_amount' => 'decimal:2',
            'foreman_commission' => 'decimal:2',
            'dividend_per_member' => 'decimal:2',
            'period_no' => 'integer',
            'total_subscribers' => 'integer',
            'auto_extend_window_seconds' => 'integer',
            'auto_extend_seconds' => 'integer',
            'extended_count' => 'integer',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ends_at' => 'datetime',
            'paused_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function chitty(): BelongsTo
    {
        return $this->belongsTo(Chitty::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(InstallmentSchedule::class, 'installment_schedule_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(AuctionParticipant::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(AuctionBid::class);
    }

    public function validBids(): HasMany
    {
        return $this->bids()->where('is_valid', true);
    }

    public function result(): HasOne
    {
        return $this->hasOne(AuctionResult::class);
    }

    public function winnerMembership(): BelongsTo
    {
        return $this->belongsTo(ChittyMembership::class, 'winner_membership_id');
    }

    /** Seconds remaining until close, based on server time (never trust client). */
    public function secondsRemaining(): int
    {
        if (! $this->ends_at || $this->status !== AuctionStatus::Live) {
            return 0;
        }

        return (int) max(0, now()->diffInSeconds($this->ends_at, false));
    }

    public function hasEnded(): bool
    {
        return $this->ends_at !== null && now()->greaterThanOrEqualTo($this->ends_at);
    }
}

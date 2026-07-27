<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionParticipant extends Model
{
    /** @use HasFactory<\Database\Factories\AuctionParticipantFactory> */
    use BelongsToCompany, HasFactory, HasUlid;

    protected $fillable = [
        'company_id', 'auction_id', 'chitty_membership_id', 'customer_id',
        'is_eligible', 'ineligible_reason', 'joined_at', 'is_present', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_eligible' => 'boolean',
            'is_present' => 'boolean',
            'joined_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(ChittyMembership::class, 'chitty_membership_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}

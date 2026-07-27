<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChittyScheme extends Model
{
    /** @use HasFactory<\Database\Factories\ChittySchemeFactory> */
    use BelongsToCompany, HasFactory, HasUlid, SoftDeletes;

    protected $fillable = [
        'company_id', 'code', 'name', 'description',
        'chit_value', 'duration_months', 'total_subscribers', 'installment_amount',
        'foreman_commission_percent', 'auction_frequency',
        'min_bid_percent', 'max_bid_percent',
        'grace_period_days', 'late_fee_type', 'late_fee_value',
        'required_kyc_level', 'is_active', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'chit_value' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'foreman_commission_percent' => 'decimal:2',
            'min_bid_percent' => 'decimal:2',
            'max_bid_percent' => 'decimal:2',
            'late_fee_value' => 'decimal:2',
            'duration_months' => 'integer',
            'total_subscribers' => 'integer',
            'grace_period_days' => 'integer',
            'required_kyc_level' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function chitties(): HasMany
    {
        return $this->hasMany(Chitty::class);
    }
}

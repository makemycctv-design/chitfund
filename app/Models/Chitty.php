<?php

namespace App\Models;

use App\Enums\ChittyStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chitty extends Model
{
    /** @use HasFactory<\Database\Factories\ChittyFactory> */
    use BelongsToCompany, HasFactory, HasUlid, SoftDeletes;

    protected $table = 'chitties';

    protected $fillable = [
        'company_id', 'branch_id', 'chitty_scheme_id', 'code', 'name',
        'chit_value', 'duration_months', 'total_subscribers', 'installment_amount',
        'foreman_commission_percent', 'auction_frequency',
        'min_bid_percent', 'max_bid_percent',
        'grace_period_days', 'late_fee_type', 'late_fee_value', 'required_kyc_level',
        'enrollment_opens_on', 'enrollment_closes_on', 'start_date', 'maturity_date',
        'auction_day', 'auction_time', 'status', 'notes', 'terms', 'meta', 'created_by',
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
            'enrollment_opens_on' => 'date',
            'enrollment_closes_on' => 'date',
            'start_date' => 'date',
            'maturity_date' => 'date',
            'status' => ChittyStatus::class,
            'meta' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(ChittyScheme::class, 'chitty_scheme_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ChittyMembership::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(InstallmentSchedule::class);
    }

    public function installmentPayments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class);
    }

    public function hasSchedule(): bool
    {
        return $this->schedules()->exists();
    }

    /** Scope to chitties currently considered live/active. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->whereIn('status', ChittyStatus::liveStatuses());
    }

    public function availableSlots(): int
    {
        return max(0, $this->total_subscribers - $this->memberships()->count());
    }
}

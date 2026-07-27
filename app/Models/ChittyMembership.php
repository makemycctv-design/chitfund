<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChittyMembership extends Model
{
    /** @use HasFactory<\Database\Factories\ChittyMembershipFactory> */
    use BelongsToCompany, HasFactory, HasUlid, SoftDeletes;

    protected $fillable = [
        'company_id', 'chitty_id', 'customer_id', 'ticket_number',
        'status', 'joined_on', 'is_prized', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'ticket_number' => 'integer',
            'joined_on' => 'date',
            'is_prized' => 'boolean',
            'status' => MembershipStatus::class,
            'meta' => 'array',
        ];
    }

    public function chitty(): BelongsTo
    {
        return $this->belongsTo(Chitty::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function installmentPayments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InstallmentPayment::class, 'chitty_membership_id');
    }

    public function guarantors(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Guarantor::class, 'chitty_membership_id');
    }
}

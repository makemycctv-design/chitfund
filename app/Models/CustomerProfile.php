<?php

namespace App\Models;

use App\Enums\KycStatus;
use App\Enums\RegistrationStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerProfile extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerProfileFactory> */
    use BelongsToCompany, HasFactory, HasUlid, SoftDeletes;

    protected $fillable = [
        'user_id', 'company_id', 'branch_id', 'customer_code',
        'date_of_birth', 'gender', 'occupation', 'annual_income',
        'address_line1', 'address_line2', 'city', 'state', 'pincode',
        'registration_status', 'approved_by', 'approved_at', 'rejection_reason',
        'kyc_status', 'kyc_level', 'kyc_verified_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'annual_income' => 'decimal:2',
            'approved_at' => 'datetime',
            'kyc_verified_at' => 'datetime',
            'kyc_level' => 'integer',
            'registration_status' => RegistrationStatus::class,
            'kyc_status' => KycStatus::class,
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function kycDocuments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KycDocument::class, 'customer_profile_id');
    }

    public function isKycVerified(): bool
    {
        return $this->kyc_status === KycStatus::Verified;
    }
}

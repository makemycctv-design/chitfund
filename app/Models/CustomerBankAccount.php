<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerBankAccount extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerBankAccountFactory> */
    use BelongsToCompany, HasFactory, HasUlid, SoftDeletes;

    protected $fillable = [
        'company_id', 'customer_id', 'account_holder_name',
        'account_number', 'account_number_last4', 'ifsc',
        'bank_name', 'branch_name', 'is_primary', 'is_verified',
        'verified_by', 'verified_at',
    ];

    protected $hidden = ['account_number'];

    protected function casts(): array
    {
        return [
            // Full account number is encrypted at rest.
            'account_number' => 'encrypted',
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}

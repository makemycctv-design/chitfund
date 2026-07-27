<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guarantor extends Model
{
    /** @use HasFactory<\Database\Factories\GuarantorFactory> */
    use BelongsToCompany, HasFactory, HasUlid, SoftDeletes;

    protected $fillable = [
        'company_id', 'chitty_membership_id', 'name', 'phone',
        'relationship', 'address', 'id_proof_type', 'id_proof_number', 'meta',
    ];

    protected $hidden = ['id_proof_number'];

    protected function casts(): array
    {
        return [
            'id_proof_number' => 'encrypted',
            'meta' => 'array',
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(ChittyMembership::class, 'chitty_membership_id');
    }
}

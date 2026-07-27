<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    /** @use HasFactory<\Database\Factories\BranchFactory> */
    use BelongsToCompany, HasFactory, HasUlid, SoftDeletes;

    protected $fillable = [
        'company_id', 'code', 'name', 'email', 'phone',
        'address_line1', 'address_line2', 'city', 'state', 'pincode',
        'manager_id', 'is_active', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function chitties(): HasMany
    {
        return $this->hasMany(Chitty::class);
    }
}

<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffProfile extends Model
{
    /** @use HasFactory<\Database\Factories\StaffProfileFactory> */
    use BelongsToCompany, HasFactory, HasUlid, SoftDeletes;

    protected $fillable = [
        'user_id', 'company_id', 'branch_id',
        'employee_code', 'designation', 'joined_on',
        'status', 'two_factor_enabled', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'joined_on' => 'date',
            'two_factor_enabled' => 'boolean',
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
}

<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    protected $fillable = [
        'name', 'legal_name', 'registration_number', 'gstin',
        'email', 'phone',
        'address_line1', 'address_line2', 'city', 'state', 'pincode', 'country',
        'currency', 'timezone', 'locale', 'is_active', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(User::class)->where('type', 'customer');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class)->where('type', 'staff');
    }

    public function chittySchemes(): HasMany
    {
        return $this->hasMany(ChittyScheme::class);
    }

    public function chitties(): HasMany
    {
        return $this->hasMany(Chitty::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }
}

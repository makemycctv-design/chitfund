<?php

namespace App\Models;

use App\Enums\ScheduleStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\InstallmentScheduleFactory> */
    use BelongsToCompany, HasFactory, HasUlid;

    protected $fillable = [
        'company_id', 'chitty_id', 'period_no', 'due_date',
        'base_installment_amount', 'status', 'auction_id', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'period_no' => 'integer',
            'due_date' => 'date',
            'base_installment_amount' => 'decimal:2',
            'status' => ScheduleStatus::class,
            'meta' => 'array',
        ];
    }

    public function chitty(): BelongsTo
    {
        return $this->belongsTo(Chitty::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }
}

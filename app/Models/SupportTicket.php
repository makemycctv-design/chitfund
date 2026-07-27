<?php

namespace App\Models;

use App\Enums\SupportTicketStatus;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportTicket extends Model
{
    /** @use HasFactory<\Database\Factories\SupportTicketFactory> */
    use BelongsToCompany, HasFactory, HasUlid, SoftDeletes;

    protected $fillable = [
        'company_id', 'customer_id', 'branch_id', 'reference', 'subject',
        'category', 'status', 'priority', 'assigned_to', 'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SupportTicketStatus::class,
            'last_message_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at');
    }
}

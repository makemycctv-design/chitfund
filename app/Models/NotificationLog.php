<?php

namespace App\Models;

use App\Enums\NotificationDeliveryStatus;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasUlid;

    protected $fillable = [
        'company_id', 'user_id', 'event_key', 'channel', 'notifiable_type',
        'status', 'provider_message_id', 'attempts', 'error', 'payload', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => NotificationDeliveryStatus::class,
            'attempts' => 'integer',
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

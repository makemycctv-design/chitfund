<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasUlid;

    protected $fillable = [
        'company_id', 'event_key', 'channel', 'name', 'subject', 'body',
        'is_active', 'whatsapp_template_name', 'whatsapp_approval_status', 'variables',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'variables' => 'array',
        ];
    }

    /**
     * Resolve the active template for an event + channel, preferring a
     * company-specific override over the global default.
     */
    public static function resolve(?int $companyId, string $eventKey, string $channel): ?self
    {
        return static::where('event_key', $eventKey)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'))
            ->orderByRaw('company_id IS NULL') // company-specific first
            ->first();
    }

    /** Render the body/subject by substituting {{token}} variables. */
    public function render(array $data): array
    {
        return [
            'subject' => $this->interpolate($this->subject ?? '', $data),
            'body' => $this->interpolate($this->body, $data),
        ];
    }

    private function interpolate(string $text, array $data): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($m) use ($data) {
            return (string) ($data[$m[1]] ?? '');
        }, $text);
    }
}

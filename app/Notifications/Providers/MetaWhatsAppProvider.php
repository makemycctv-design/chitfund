<?php

namespace App\Notifications\Providers;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Meta WhatsApp Cloud API driver. Active only when credentials are configured.
 * Kept minimal: posts a text message to the Graph API and returns the message
 * id. Template messages / approval are governed by notification_templates.
 */
class MetaWhatsAppProvider implements WhatsAppProvider
{
    public function __construct(
        private readonly ?string $token,
        private readonly ?string $phoneNumberId,
    ) {}

    public function send(string $toPhone, string $message): string
    {
        if (! $this->token || ! $this->phoneNumberId) {
            throw new RuntimeException('WhatsApp credentials are not configured.');
        }

        $response = Http::withToken($this->token)
            ->post("https://graph.facebook.com/v20.0/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $toPhone,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('WhatsApp send failed: '.$response->body());
        }

        return (string) data_get($response->json(), 'messages.0.id', 'unknown');
    }
}

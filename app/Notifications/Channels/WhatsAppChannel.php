<?php

namespace App\Notifications\Channels;

use App\Notifications\Providers\WhatsAppProvider;
use Illuminate\Notifications\Notification;

/**
 * Custom Laravel notification channel that delivers via the configured
 * WhatsAppProvider. Returning the provider message id lets the delivery-logging
 * listener record it against the NotificationLog.
 */
class WhatsAppChannel
{
    public function __construct(private readonly WhatsAppProvider $provider) {}

    public function send(object $notifiable, Notification $notification): ?string
    {
        if (! method_exists($notification, 'toWhatsApp')) {
            return null;
        }

        $payload = $notification->toWhatsApp($notifiable);
        $to = $payload['to'] ?? $notifiable->routeNotificationFor('whatsapp', $notification) ?? $notifiable->phone ?? null;

        if (! $to || empty($payload['message'])) {
            return null;
        }

        // Throws on failure -> Laravel dispatches NotificationFailed.
        return $this->provider->send($to, $payload['message']);
    }
}

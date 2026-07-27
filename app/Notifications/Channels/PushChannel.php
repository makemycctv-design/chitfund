<?php

namespace App\Notifications\Channels;

use App\Notifications\Providers\PushProvider;
use Illuminate\Notifications\Notification;

class PushChannel
{
    public function __construct(private readonly PushProvider $provider) {}

    public function send(object $notifiable, Notification $notification): ?string
    {
        if (! method_exists($notification, 'toPush')) {
            return null;
        }

        $token = $notifiable->routeNotificationFor('push', $notification) ?? ($notifiable->push_token ?? null);
        if (! $token) {
            return null; // no registered device (mobile app, Phase 5)
        }

        $payload = $notification->toPush($notifiable);

        return $this->provider->send($token, $payload['title'] ?? '', $payload['body'] ?? '');
    }
}

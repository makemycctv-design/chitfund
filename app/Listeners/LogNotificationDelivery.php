<?php

namespace App\Listeners;

use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationLog;
use App\Notifications\ChittyNotification;
use App\Notifications\Channels\PushChannel;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Writes a NotificationLog row for every channel send/failure so the admin has
 * delivery-status tracking and a basis for retries.
 */
class LogNotificationDelivery
{
    public function handleSent(NotificationSent $event): void
    {
        $this->record($event->notification, $event->notifiable, $event->channel, NotificationDeliveryStatus::Sent, [
            'provider_message_id' => is_string($event->response) ? $event->response : null,
        ]);
    }

    public function handleFailed(NotificationFailed $event): void
    {
        $this->record($event->notification, $event->notifiable, $event->channel, NotificationDeliveryStatus::Failed, [
            'error' => json_encode($event->data ?? []),
        ]);
    }

    private function record($notification, $notifiable, string $channel, NotificationDeliveryStatus $status, array $extra = []): void
    {
        if (! $notification instanceof ChittyNotification) {
            return;
        }

        NotificationLog::create([
            'company_id' => $notifiable->company_id ?? null,
            'user_id' => $notifiable->getKey(),
            'event_key' => $notification->eventKey(),
            'channel' => $this->normalizeChannel($channel),
            'notifiable_type' => get_class($notifiable),
            'status' => $status->value,
            'provider_message_id' => $extra['provider_message_id'] ?? null,
            'attempts' => 1,
            'error' => $extra['error'] ?? null,
            'payload' => method_exists($notification, 'toArray') ? $notification->toArray($notifiable) : null,
            'sent_at' => $status === NotificationDeliveryStatus::Sent ? now() : null,
        ]);
    }

    private function normalizeChannel(string $channel): string
    {
        return match ($channel) {
            WhatsAppChannel::class => 'whatsapp',
            PushChannel::class => 'push',
            default => $channel,
        };
    }
}

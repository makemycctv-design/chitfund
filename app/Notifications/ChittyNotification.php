<?php

namespace App\Notifications;

use App\Enums\NotificationChannel;
use App\Models\NotificationTemplate;
use App\Notifications\Channels\PushChannel;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for all app notifications. External channels are queued. Each channel's
 * content is rendered from a configurable NotificationTemplate (per event +
 * channel, company-overridable) with a safe fallback. Channels are filtered by
 * the recipient's per-channel preferences and by whether a template exists.
 */
abstract class ChittyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** Stable key used to resolve templates + log delivery, e.g. "payment.received". */
    abstract public function eventKey(): string;

    /** Variables available to templates for this notification. */
    abstract protected function variables(object $notifiable): array;

    /** A short human title used as the fallback subject/in-app title. */
    abstract protected function title(): string;

    /** Candidate channels; subclasses may narrow this. */
    protected function candidateChannels(): array
    {
        return [
            NotificationChannel::Database->value,
            NotificationChannel::Mail->value,
            NotificationChannel::WhatsApp->value,
            NotificationChannel::Push->value,
        ];
    }

    /** Optional deep link surfaced in the in-app payload. */
    protected function actionUrl(object $notifiable): ?string
    {
        return null;
    }

    public function via(object $notifiable): array
    {
        $companyId = $notifiable->company_id ?? null;
        $channels = [];

        foreach ($this->candidateChannels() as $channel) {
            if (! $notifiable->acceptsNotificationOn($channel)) {
                continue;
            }
            if ($channel === NotificationChannel::Mail->value && empty($notifiable->email)) {
                continue;
            }
            if ($channel === NotificationChannel::WhatsApp->value && empty($notifiable->phone)) {
                continue;
            }
            // Push only when the recipient has a registered device.
            if ($channel === NotificationChannel::Push->value
                && empty($notifiable->routeNotificationFor('push'))) {
                continue;
            }

            // In-app is always available; others require a template.
            if ($channel === NotificationChannel::Database->value) {
                $channels[] = 'database';

                continue;
            }
            if (! NotificationTemplate::resolve($companyId, $this->eventKey(), $channel)) {
                continue;
            }

            $channels[] = match ($channel) {
                NotificationChannel::Mail->value => 'mail',
                NotificationChannel::WhatsApp->value => WhatsAppChannel::class,
                NotificationChannel::Push->value => PushChannel::class,
                default => null,
            };
        }

        return array_values(array_filter($channels));
    }

    /** Render subject/body for a channel from its template (or fallback). */
    protected function render(object $notifiable, string $channel): array
    {
        $data = $this->variables($notifiable);
        $template = NotificationTemplate::resolve($notifiable->company_id ?? null, $this->eventKey(), $channel);

        if ($template) {
            return $template->render($data);
        }

        // Fallback: title + a generic one-line body from the variables.
        return [
            'subject' => $this->title(),
            'body' => $this->title().(isset($data['name']) ? ", {$data['name']}." : '.'),
        ];
    }

    public function toArray(object $notifiable): array
    {
        $rendered = $this->render($notifiable, NotificationChannel::Database->value);

        return [
            'event' => $this->eventKey(),
            'title' => $rendered['subject'] ?: $this->title(),
            'message' => $rendered['body'],
            'url' => $this->actionUrl($notifiable),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rendered = $this->render($notifiable, NotificationChannel::Mail->value);

        $mail = (new MailMessage())->subject($rendered['subject'] ?: $this->title());
        foreach (preg_split('/\n{2,}/', $rendered['body']) as $paragraph) {
            $mail->line(trim($paragraph));
        }
        if ($url = $this->actionUrl($notifiable)) {
            $mail->action('View', url($url));
        }

        return $mail;
    }

    /** @return array{to: ?string, message: string} */
    public function toWhatsApp(object $notifiable): array
    {
        $rendered = $this->render($notifiable, NotificationChannel::WhatsApp->value);

        return ['to' => $notifiable->phone ?? null, 'message' => $rendered['body']];
    }

    /** @return array{title: string, body: string} */
    public function toPush(object $notifiable): array
    {
        $rendered = $this->render($notifiable, NotificationChannel::Push->value);

        return ['title' => $rendered['subject'] ?: $this->title(), 'body' => $rendered['body']];
    }
}

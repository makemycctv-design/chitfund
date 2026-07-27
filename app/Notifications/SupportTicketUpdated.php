<?php

namespace App\Notifications;

use App\Models\SupportTicket;

class SupportTicketUpdated extends ChittyNotification
{
    public function __construct(public SupportTicket $ticket, public string $summary) {}

    public function eventKey(): string
    {
        return 'support.updated';
    }

    protected function title(): string
    {
        return 'Support Ticket Update';
    }

    protected function candidateChannels(): array
    {
        return ['database', 'mail'];
    }

    protected function variables(object $notifiable): array
    {
        return [
            'name' => $notifiable->name,
            'reference' => $this->ticket->reference,
            'subject' => $this->ticket->subject,
            'status' => $this->ticket->status->value,
            'summary' => $this->summary,
        ];
    }

    protected function actionUrl(object $notifiable): ?string
    {
        return $notifiable->isStaff() ? '/admin/support' : '/portal/support';
    }
}

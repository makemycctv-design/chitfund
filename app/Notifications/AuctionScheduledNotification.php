<?php

namespace App\Notifications;

use App\Models\Auction;

class AuctionScheduledNotification extends ChittyNotification
{
    public function __construct(public Auction $auction) {}

    public function eventKey(): string
    {
        return 'auction.scheduled';
    }

    protected function title(): string
    {
        return 'Auction Scheduled';
    }

    protected function variables(object $notifiable): array
    {
        return [
            'name' => $notifiable->name,
            'chitty' => $this->auction->chitty?->code ?? '',
            'period' => (string) $this->auction->period_no,
            'scheduled_at' => $this->auction->scheduled_at?->format('d M Y, h:i A') ?? '',
        ];
    }

    protected function actionUrl(object $notifiable): ?string
    {
        return "/portal/auctions/{$this->auction->ulid}";
    }
}

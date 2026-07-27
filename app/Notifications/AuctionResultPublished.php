<?php

namespace App\Notifications;

use App\Models\AuctionResult;

class AuctionResultPublished extends ChittyNotification
{
    public function __construct(public AuctionResult $result, public bool $isWinner = false) {}

    public function eventKey(): string
    {
        return $this->isWinner ? 'auction.winner' : 'auction.result';
    }

    protected function title(): string
    {
        return $this->isWinner ? 'You Won the Auction!' : 'Auction Result';
    }

    protected function variables(object $notifiable): array
    {
        return [
            'name' => $notifiable->name,
            'prize' => number_format((float) $this->result->prize_amount, 2),
            'dividend' => number_format((float) $this->result->dividend_per_member, 2),
            'chitty' => $this->result->chitty?->code ?? '',
        ];
    }

    protected function actionUrl(object $notifiable): ?string
    {
        return "/portal/auctions/{$this->result->auction->ulid}";
    }
}

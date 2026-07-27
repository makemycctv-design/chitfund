<?php

namespace App\Enums;

/**
 * The bidding model. Kept configurable rather than hard-coded so a company can
 * run auctions per their approved business rules.
 *
 *  - MaxDiscount: subscribers bid the discount they will forgo; the HIGHEST
 *    valid discount wins (most common Indian chit model). Prize = chit − discount.
 *  - MinPrize: subscribers bid the prize amount they will accept; the LOWEST
 *    valid prize wins.
 *  - LowestBid: generic lowest-amount-wins.
 */
enum AuctionMethod: string
{
    case MaxDiscount = 'max_discount';
    case MinPrize = 'min_prize';
    case LowestBid = 'lowest_bid';

    public function label(): string
    {
        return match ($this) {
            self::MaxDiscount => 'Maximum Discount',
            self::MinPrize => 'Minimum Prize',
            self::LowestBid => 'Lowest Bid',
        };
    }

    /** True when a higher bid amount beats a lower one. */
    public function higherWins(): bool
    {
        return $this === self::MaxDiscount;
    }
}

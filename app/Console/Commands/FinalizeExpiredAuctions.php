<?php

namespace App\Console\Commands;

use App\Domain\Auctions\FinalizeAuction;
use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Closes live auctions whose server end time has passed. Runs every minute so
 * auctions finalize automatically even if an officer doesn't click "Finalize".
 */
class FinalizeExpiredAuctions extends Command
{
    protected $signature = 'chittyfund:finalize-expired-auctions';

    protected $description = 'Finalize live auctions past their end time';

    public function handle(FinalizeAuction $finalizer): int
    {
        $expired = Auction::where('status', AuctionStatus::Live->value)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($expired as $auction) {
            // Prefer the scheduling officer; fall back to any company Super Admin.
            $officer = $auction->created_by
                ? User::find($auction->created_by)
                : User::where('company_id', $auction->company_id)->where('type', 'staff')->first();

            if (! $officer) {
                continue;
            }

            try {
                $finalizer->handle($auction, $officer);
                $this->info("Finalized auction {$auction->ulid}.");
            } catch (\Throwable $e) {
                $this->error("Failed to finalize {$auction->ulid}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}

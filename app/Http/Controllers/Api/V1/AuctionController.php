<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auctions\AuctionStatePresenter;
use App\Domain\Auctions\BidRejectedException;
use App\Domain\Auctions\PlaceBid;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuctionController extends Controller
{
    public function __construct(private readonly AuctionStatePresenter $presenter) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $auctions = Auction::whereHas('participants', fn ($q) => $q->where('customer_id', $user->id))
            ->with('chitty:id,code,name')
            ->latest('scheduled_at')
            ->get()
            ->map(fn (Auction $a) => [
                'id' => $a->ulid,
                'chitty' => $a->chitty?->code,
                'periodNo' => $a->period_no,
                'status' => $a->status->value,
                'endsAt' => $a->ends_at?->toIso8601String(),
            ]);

        return ApiResponse::success($auctions);
    }

    public function show(Request $request, Auction $auction): JsonResponse
    {
        if (! $this->isParticipant($request, $auction)) {
            return ApiResponse::error('Auction not found.', 404);
        }

        return ApiResponse::success($this->presenter->present($auction));
    }

    public function bid(Request $request, Auction $auction, PlaceBid $placeBid): JsonResponse
    {
        if (! $this->isParticipant($request, $auction)) {
            return ApiResponse::error('Auction not found.', 404);
        }
        abort_unless($request->user()->can('bidding.participate'), 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $bid = $placeBid->handle($auction, $request->user(), (string) $validated['amount'], $validated['idempotency_key'] ?? null);
        } catch (BidRejectedException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success([
            'bidId' => $bid->ulid,
            'amount' => (float) $bid->amount,
        ], 'Bid placed.');
    }

    private function isParticipant(Request $request, Auction $auction): bool
    {
        return $auction->participants()->where('customer_id', $request->user()->id)->exists();
    }
}

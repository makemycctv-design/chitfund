<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Auctions\AuctionStatePresenter;
use App\Domain\Auctions\BidRejectedException;
use App\Domain\Auctions\PlaceBid;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Customer-facing auction room. A customer may only see/join auctions where
 * they are an eligible participant — enforced on every action, server-side.
 */
class AuctionController extends Controller
{
    public function __construct(private readonly AuctionStatePresenter $presenter) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $auctions = Auction::whereHas('participants', fn ($q) => $q->where('customer_id', $user->id))
            ->with('chitty:id,code,name')
            ->latest('scheduled_at')
            ->get()
            ->map(fn (Auction $a) => [
                'id' => $a->ulid,
                'chitty' => $a->chitty?->code,
                'chittyName' => $a->chitty?->name,
                'periodNo' => $a->period_no,
                'status' => $a->status->value,
                'scheduledAt' => $a->scheduled_at?->toIso8601String(),
                'endsAt' => $a->ends_at?->toIso8601String(),
                'eligible' => (bool) optional($a->participants->firstWhere('customer_id', $user->id))->is_eligible,
            ]);

        return Inertia::render('portal/auctions/index', ['auctions' => $auctions]);
    }

    public function show(Request $request, Auction $auction): Response
    {
        $participant = $this->participant($request, $auction);

        return Inertia::render('portal/auctions/show', [
            'auction' => [
                'id' => $auction->ulid,
                'chitty' => $auction->chitty?->code,
                'chittyName' => $auction->chitty?->name,
                'periodNo' => $auction->period_no,
            ],
            'state' => $this->presenter->present($auction),
            'me' => [
                'ticketNumber' => $participant->membership?->ticket_number,
                'isEligible' => $participant->is_eligible,
                'ineligibleReason' => $participant->ineligible_reason,
            ],
        ]);
    }

    public function state(Request $request, Auction $auction): JsonResponse
    {
        $participant = $this->participant($request, $auction);

        // Presence heartbeat.
        $participant->forceFill(['is_present' => true, 'last_seen_at' => now()])->save();

        return response()->json($this->presenter->present($auction));
    }

    public function bid(Request $request, Auction $auction, PlaceBid $placeBid): RedirectResponse
    {
        $this->participant($request, $auction); // ensures participation
        abort_unless($request->user()->can('bidding.participate'), 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $placeBid->handle($auction, $request->user(), (string) $validated['amount'], $validated['idempotency_key'] ?? null);
        } catch (BidRejectedException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bid placed.');
    }

    private function participant(Request $request, Auction $auction): AuctionParticipant
    {
        $participant = $auction->participants()
            ->where('customer_id', $request->user()->id)
            ->with('membership:id,ticket_number')
            ->first();

        abort_unless($participant !== null, 404);

        return $participant;
    }
}

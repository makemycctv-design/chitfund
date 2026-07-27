<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auctions\AuctionLifecycle;
use App\Domain\Auctions\AuctionStatePresenter;
use App\Domain\Auctions\FinalizeAuction;
use App\Domain\Auctions\ScheduleAuction;
use App\Enums\AuctionStatus;
use App\Enums\ChittyStatus;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\Chitty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AuctionController extends Controller
{
    public function __construct(private readonly AuctionStatePresenter $presenter) {}

    private function companyId(Request $request): int
    {
        return (int) $request->user()->company_id;
    }

    private function guard(Request $request, string $permission, ?Auction $auction = null): void
    {
        abort_unless($request->user()->can($permission), 403);
        if ($auction) {
            abort_unless($auction->company_id === $this->companyId($request), 403);
        }
    }

    public function index(Request $request): Response
    {
        $this->guard($request, 'chitties.view');

        $auctions = Auction::where('company_id', $this->companyId($request))
            ->with('chitty:id,code,name')
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->latest('scheduled_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Auction $a) => [
                'id' => $a->ulid,
                'chitty' => $a->chitty?->code,
                'chittyName' => $a->chitty?->name,
                'periodNo' => $a->period_no,
                'status' => $a->status->value,
                'scheduledAt' => $a->scheduled_at?->toIso8601String(),
                'endsAt' => $a->ends_at?->toIso8601String(),
                'prizeAmount' => $a->prize_amount !== null ? (float) $a->prize_amount : null,
            ]);

        return Inertia::render('admin/auctions/index', [
            'auctions' => $auctions,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->guard($request, 'auctions.start');

        // Chitties with a generated schedule; expose periods not yet auctioned.
        $chitties = Chitty::where('company_id', $this->companyId($request))
            ->whereIn('status', ChittyStatus::liveStatuses())
            ->whereHas('schedules')
            ->with(['schedules:id,chitty_id,period_no,due_date', 'auctions:id,chitty_id,period_no'])
            ->get()
            ->map(function (Chitty $c) {
                $auctioned = $c->auctions->pluck('period_no')->all();
                $available = $c->schedules
                    ->reject(fn ($s) => in_array($s->period_no, $auctioned, true))
                    ->map(fn ($s) => ['periodNo' => $s->period_no, 'dueDate' => $s->due_date?->toDateString()])
                    ->values();

                return ['id' => $c->ulid, 'label' => "{$c->name} ({$c->code})", 'periods' => $available];
            })
            ->filter(fn ($c) => count($c['periods']) > 0)
            ->values();

        return Inertia::render('admin/auctions/create', ['chitties' => $chitties]);
    }

    public function store(Request $request, ScheduleAuction $scheduler): RedirectResponse
    {
        $this->guard($request, 'auctions.start');

        $validated = $request->validate([
            'chitty_id' => ['required', 'string'],
            'period_no' => ['required', 'integer', 'min:1'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $chitty = Chitty::where('ulid', $validated['chitty_id'])
            ->where('company_id', $this->companyId($request))
            ->firstOrFail();

        try {
            $auction = $scheduler->handle(
                $chitty,
                (int) $validated['period_no'],
                Carbon::parse($validated['scheduled_at']),
                $request->user(),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Notify eligible participants that an auction is scheduled.
        $auction->load('participants.customer');
        foreach ($auction->participants->where('is_eligible', true) as $participant) {
            $participant->customer?->notify(new \App\Notifications\AuctionScheduledNotification($auction));
        }

        return redirect()->route('admin.auctions.show', $auction)->with('success', 'Auction scheduled.');
    }

    public function show(Request $request, Auction $auction): Response
    {
        $this->guard($request, 'chitties.view', $auction);

        $auction->load('chitty:id,code,name');

        $participants = $auction->participants()
            ->with('membership:id,ticket_number', 'customer:id,name')
            ->get()
            ->map(fn ($p) => [
                'ticketNumber' => $p->membership?->ticket_number,
                'customerName' => $p->customer?->name,
                'isEligible' => $p->is_eligible,
                'ineligibleReason' => $p->ineligible_reason,
                'isPresent' => $p->is_present,
            ]);

        return Inertia::render('admin/auctions/show', [
            'auction' => [
                'id' => $auction->ulid,
                'chitty' => $auction->chitty?->code,
                'chittyName' => $auction->chitty?->name,
                'periodNo' => $auction->period_no,
                'methodLabel' => $auction->method->label(),
                'foremanCommissionPercent' => (float) $auction->foreman_commission_percent,
            ],
            'state' => $this->presenter->present($auction),
            'participants' => $participants,
            'can' => [
                'start' => $request->user()->can('auctions.start'),
                'pause' => $request->user()->can('auctions.pause'),
                'resume' => $request->user()->can('auctions.resume'),
                'close' => $request->user()->can('auctions.close') || $request->user()->can('auctions.finalize'),
                'cancel' => $request->user()->can('auctions.cancel'),
            ],
        ]);
    }

    public function state(Request $request, Auction $auction): JsonResponse
    {
        $this->guard($request, 'chitties.view', $auction);

        return response()->json($this->presenter->present($auction));
    }

    public function start(Request $request, Auction $auction, AuctionLifecycle $lifecycle): RedirectResponse
    {
        $this->guard($request, 'auctions.start', $auction);
        $minutes = (int) $request->integer('duration_minutes', 5);

        try {
            $lifecycle->start($auction, $request->user(), max(60, $minutes * 60));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Auction started.');
    }

    public function pause(Request $request, Auction $auction, AuctionLifecycle $lifecycle): RedirectResponse
    {
        $this->guard($request, 'auctions.pause', $auction);
        $this->run(fn () => $lifecycle->pause($auction, $request->user()));

        return back();
    }

    public function resume(Request $request, Auction $auction, AuctionLifecycle $lifecycle): RedirectResponse
    {
        $this->guard($request, 'auctions.resume', $auction);
        $this->run(fn () => $lifecycle->resume($auction, $request->user()));

        return back();
    }

    public function cancel(Request $request, Auction $auction, AuctionLifecycle $lifecycle): RedirectResponse
    {
        $this->guard($request, 'auctions.cancel', $auction);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $this->run(fn () => $lifecycle->cancel($auction, $request->user(), $validated['reason']));

        return back()->with('success', 'Auction cancelled.');
    }

    public function finalize(Request $request, Auction $auction, FinalizeAuction $finalizer): RedirectResponse
    {
        abort_unless(
            $request->user()->can('auctions.finalize') || $request->user()->can('auctions.close'),
            403,
        );
        abort_unless($auction->company_id === $this->companyId($request), 403);

        try {
            $finalizer->handle($auction, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.auctions.show', $auction)->with('success', 'Auction finalized.');
    }

    private function run(callable $fn): void
    {
        try {
            $fn();
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }
}

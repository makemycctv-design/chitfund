<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ChittyStatus;
use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Models\ChittyMembership;
use App\Models\InstallmentPayment;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Customer portal dashboard. Shows the subscriber's live chitties and their
     * monthly obligation derived from real membership + chitty data. Deep
     * installment ledgers and auction results are layered in Phase 2/3.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $profile = $user->customerProfile;

        $memberships = ChittyMembership::query()
            ->where('customer_id', $user->id)
            ->where('status', MembershipStatus::Active->value)
            ->with('chitty:id,ulid,code,name,installment_amount,auction_day,auction_time,status,maturity_date')
            ->get();

        $activeChitties = $memberships
            ->filter(fn ($m) => $m->chitty && in_array($m->chitty->status->value, ChittyStatus::liveStatuses(), true))
            ->map(function (ChittyMembership $m) {
                return [
                    'id' => $m->chitty->ulid,
                    'code' => $m->chitty->code,
                    'name' => $m->chitty->name,
                    'ticketNumber' => $m->ticket_number,
                    'installmentAmount' => (float) $m->chitty->installment_amount,
                    'nextAuctionDate' => $this->nextAuctionDate($m->chitty->auction_day),
                    'maturityDate' => optional($m->chitty->maturity_date)->toDateString(),
                    'isPrized' => $m->is_prized,
                ];
            })->values();

        // Real figures from the installment ledger (Phase 2): the next unpaid
        // obligation and the total overdue amount.
        $outstanding = InstallmentPayment::where('customer_id', $user->id)
            ->outstanding()
            ->orderBy('due_date')
            ->get();

        $nextDue = $outstanding->first();
        $nextDueAmount = $nextDue ? (float) $nextDue->outstanding() : 0.0;
        $nextDueDate = $nextDue?->due_date?->toDateString();

        $overdueAmount = (float) $outstanding
            ->filter(fn (InstallmentPayment $p) => $p->due_date?->isPast())
            ->reduce(fn (string $c, InstallmentPayment $p) => Money::add($c, $p->outstanding()), '0.00');

        $upcomingAuction = $activeChitties->pluck('nextAuctionDate')->filter()->sort()->first();

        return Inertia::render('portal/dashboard', [
            'summary' => [
                'activeChittyCount' => $activeChitties->count(),
                'nextDueAmount' => $nextDueAmount,
                'nextDueDate' => $nextDueDate ?? $upcomingAuction,
                'overdueAmount' => $overdueAmount,
                'upcomingAuctionDate' => $upcomingAuction,
                'registrationStatus' => $profile?->registration_status?->value,
                'kycStatus' => $profile?->kyc_status?->value,
                'kycLevel' => $profile?->kyc_level ?? 0,
            ],
            'activeChitties' => $activeChitties,
        ]);
    }

    /**
     * Compute the next occurrence of the chitty's auction day-of-month.
     */
    private function nextAuctionDate(?int $day): ?string
    {
        if (! $day) {
            return null;
        }

        $now = Carbon::now();
        $candidate = $now->copy()->day(min($day, $now->daysInMonth));

        if ($candidate->lte($now)) {
            $next = $now->copy()->addMonthNoOverflow()->startOfMonth();
            $candidate = $next->day(min($day, $next->daysInMonth));
        }

        return $candidate->toDateString();
    }
}

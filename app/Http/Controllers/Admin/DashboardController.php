<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuctionStatus;
use App\Enums\ChittyStatus;
use App\Enums\KycStatus;
use App\Enums\RegistrationStatus;
use App\Enums\SupportTicketStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\Branch;
use App\Models\Chitty;
use App\Models\CustomerProfile;
use App\Models\InstallmentPayment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Role- and branch-aware back-office dashboard.
     *
     * Every figure is computed live and scoped to what the signed-in user is
     * allowed to see:
     *  - Super Admin  -> all companies, all branches (company_id is null).
     *  - Company Owner -> their company, all branches, plus a per-branch
     *    breakdown table.
     *  - Branch staff -> only their own branch's figures and action items.
     *
     * The frontend decides which cards/quick-actions to show from the user's
     * permissions, so this endpoint simply returns every figure the user is
     * scoped to.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $companyId = $user->company_id;        // null => Super Admin, all companies
        $branchScope = $user->branchScopeId(); // null => all branches (super admin / company owner)

        // Reusable scoping helpers. $branchCol applies only to models that
        // physically carry a branch_id column.
        $companyScope = fn (Builder $q): Builder => $companyId ? $q->where('company_id', $companyId) : $q;
        $branchCol = fn (Builder $q): Builder => $branchScope !== null ? $q->where('branch_id', $branchScope) : $q;

        $liveStatuses = ChittyStatus::liveStatuses();

        // --- Chitties (carries branch_id) ---
        $chitty = fn (): Builder => $branchCol($companyScope(Chitty::query()));
        $activeChitties = $chitty()->whereIn('status', $liveStatuses)->count();
        $totalChitties = $chitty()->count();
        $portfolioValue = (float) $chitty()->whereIn('status', $liveStatuses)->sum('chit_value');

        // --- Subscribers (User customers carry branch_id) ---
        $totalSubscribers = $branchCol(
            $companyScope(User::query()->where('type', UserType::Customer->value))
        )->count();

        // --- Branches ---
        $branchQuery = $companyScope(Branch::query());
        if ($branchScope !== null) {
            $branchQuery->where('id', $branchScope);
        }
        $totalBranches = $branchQuery->count();

        // --- Customer profiles (carry branch_id): approvals + KYC queue ---
        $profile = fn (): Builder => $branchCol($companyScope(CustomerProfile::query()));
        $pendingRegistrations = $profile()->where('registration_status', RegistrationStatus::Pending->value)->count();
        $pendingKyc = $profile()->whereIn('kyc_status', [KycStatus::Pending->value, KycStatus::Submitted->value])->count();

        // --- Auctions (carry branch_id) ---
        $auction = fn (): Builder => $branchCol($companyScope(Auction::query()));
        $liveAuctions = $auction()->where('status', AuctionStatus::Live->value)->count();
        $scheduledAuctions = $auction()->where('status', AuctionStatus::Scheduled->value)->count();
        $upcomingAuctions = $liveAuctions + $scheduledAuctions;

        // --- Overdue installments (no branch_id -> scope through the chitty) ---
        $overdueCount = $this->overdueQuery($companyScope, $branchScope)->count();
        $overdueAmount = (float) $this->overdueQuery($companyScope, $branchScope)
            ->selectRaw('COALESCE(SUM(amount_due + late_fee - amount_paid), 0) as total')
            ->value('total');

        // --- Support tickets (carry branch_id) ---
        $openTickets = $branchCol($companyScope(SupportTicket::query()))
            ->whereIn('status', [SupportTicketStatus::Open->value, SupportTicketStatus::Pending->value])
            ->count();

        // --- Chitties-by-status chart (scoped) ---
        $statusCounts = $chitty()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $chittiesByStatus = collect(ChittyStatus::cases())->map(fn (ChittyStatus $s) => [
            'status' => $s->value,
            'label' => $s->label(),
            'count' => (int) ($statusCounts[$s->value] ?? 0),
        ])->values();

        // --- Per-branch breakdown (only for company-wide viewers) ---
        $branchBreakdown = $branchScope === null
            ? $this->branchBreakdown($companyScope, $liveStatuses)
            : [];

        $recentActivity = Activity::query()
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Activity $a) => [
                'id' => $a->id,
                'description' => $a->description,
                'subject_type' => class_basename($a->subject_type ?? ''),
                'causer' => optional($a->causer)->name,
                'created_at' => $a->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/dashboard', [
            'role' => [
                'name' => $user->getRoleNames()->first(),
                'label' => $this->roleLabel($user),
                'scopeLabel' => $this->scopeLabel($user, $companyId, $branchScope),
                'isBranchScoped' => $branchScope !== null,
            ],
            'stats' => [
                'activeChitties' => $activeChitties,
                'totalChitties' => $totalChitties,
                'totalSubscribers' => $totalSubscribers,
                'totalBranches' => $totalBranches,
                'pendingRegistrations' => $pendingRegistrations,
                'pendingKyc' => $pendingKyc,
                'liveAuctions' => $liveAuctions,
                'upcomingAuctions' => $upcomingAuctions,
                'overdueCount' => $overdueCount,
                'overdueAmount' => $overdueAmount,
                'openTickets' => $openTickets,
                'portfolioValue' => $portfolioValue,
            ],
            'chittiesByStatus' => $chittiesByStatus,
            'branchBreakdown' => $branchBreakdown,
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Fresh overdue-installment query, company- and (optionally) branch-scoped.
     * Installments have no branch_id, so the branch filter goes through the
     * parent chitty.
     */
    private function overdueQuery(callable $companyScope, ?int $branchScope): Builder
    {
        $query = $companyScope(InstallmentPayment::query())->overdue();

        if ($branchScope !== null) {
            $query->whereHas('chitty', fn (Builder $q) => $q->where('branch_id', $branchScope));
        }

        return $query;
    }

    /**
     * Per-branch KPI rows for company-wide viewers (owners / super admin).
     *
     * @return array<int, array<string, mixed>>
     */
    private function branchBreakdown(callable $companyScope, array $liveStatuses): array
    {
        $branches = $companyScope(Branch::query())->orderBy('name')->get(['id', 'name', 'code']);

        return $branches->map(function (Branch $b) use ($liveStatuses) {
            $overdueAmount = (float) InstallmentPayment::query()
                ->overdue()
                ->whereHas('chitty', fn (Builder $q) => $q->where('branch_id', $b->id))
                ->selectRaw('COALESCE(SUM(amount_due + late_fee - amount_paid), 0) as total')
                ->value('total');

            return [
                'id' => $b->id,
                'name' => $b->name,
                'code' => $b->code,
                'activeChitties' => Chitty::where('branch_id', $b->id)->whereIn('status', $liveStatuses)->count(),
                'subscribers' => User::where('branch_id', $b->id)->where('type', UserType::Customer->value)->count(),
                'pendingApprovals' => CustomerProfile::where('branch_id', $b->id)
                    ->where('registration_status', RegistrationStatus::Pending->value)->count(),
                'overdueAmount' => $overdueAmount,
            ];
        })->values()->all();
    }

    private function roleLabel(User $user): string
    {
        return match (true) {
            $user->hasRole(Rbac::SUPER_ADMIN) => 'Super Admin',
            $user->hasRole(Rbac::COMPANY_OWNER) => 'Company Owner',
            $user->hasRole(Rbac::BRANCH_MANAGER) => 'Branch Manager',
            default => (string) ($user->getRoleNames()->first() ?? 'Staff'),
        };
    }

    private function scopeLabel(User $user, ?int $companyId, ?int $branchScope): string
    {
        if ($companyId === null && $user->hasRole(Rbac::SUPER_ADMIN)) {
            return 'All companies';
        }

        if ($branchScope === null) {
            return 'All branches';
        }

        return $user->branch?->name ?? 'Your branch';
    }
}

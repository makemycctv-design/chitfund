<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChittyStatus;
use App\Enums\KycStatus;
use App\Enums\RegistrationStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Chitty;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Back-office dashboard. Every figure is computed live from the database
     * and scoped to the signed-in user's company (Super Admin sees all
     * companies). Finance/auction figures that depend on Phase 2/3 tables are
     * surfaced as the real values we can already compute today.
     */
    public function __invoke(Request $request): Response
    {
        $companyId = $request->user()->company_id; // null => Super Admin, all companies

        $scope = fn ($query) => $companyId ? $query->where('company_id', $companyId) : $query;

        $liveStatuses = ChittyStatus::liveStatuses();

        $activeChitties = $scope(Chitty::query())->whereIn('status', $liveStatuses)->count();
        $totalChitties = $scope(Chitty::query())->count();

        $totalSubscribers = $scope(User::query())->where('type', UserType::Customer->value)->count();
        $totalBranches = $scope(Branch::query())->count();

        $pendingRegistrations = $scope(CustomerProfile::query())
            ->where('registration_status', RegistrationStatus::Pending->value)->count();

        $pendingKyc = $scope(CustomerProfile::query())
            ->whereIn('kyc_status', [KycStatus::Pending->value, KycStatus::Submitted->value])->count();

        $upcomingAuctions = $scope(Chitty::query())
            ->whereIn('status', [ChittyStatus::AuctionScheduled->value, ChittyStatus::Active->value])
            ->count();

        // Committed portfolio value across live chitties (real, in INR).
        $portfolioValue = (float) $scope(Chitty::query())
            ->whereIn('status', $liveStatuses)->sum('chit_value');

        // Distribution of chitties by lifecycle status (drives the dashboard chart).
        $statusCounts = $scope(Chitty::query())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $chittiesByStatus = collect(ChittyStatus::cases())->map(fn (ChittyStatus $s) => [
            'status' => $s->value,
            'label' => $s->label(),
            'count' => (int) ($statusCounts[$s->value] ?? 0),
        ])->values();

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
            'stats' => [
                'activeChitties' => $activeChitties,
                'totalChitties' => $totalChitties,
                'totalSubscribers' => $totalSubscribers,
                'totalBranches' => $totalBranches,
                'pendingRegistrations' => $pendingRegistrations,
                'pendingKyc' => $pendingKyc,
                'upcomingAuctions' => $upcomingAuctions,
                'portfolioValue' => $portfolioValue,
            ],
            'chittiesByStatus' => $chittiesByStatus,
            'recentActivity' => $recentActivity,
        ]);
    }
}

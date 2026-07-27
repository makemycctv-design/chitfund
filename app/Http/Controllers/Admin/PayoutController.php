<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PayoutStatus;
use App\Http\Controllers\Controller;
use App\Models\PrizePayout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Prize payout maker-checker. Payouts are created (pending) by the auction
 * finalizer; a payouts.approve staff member approves, then marks paid. The
 * finalizer and approver are always different actors.
 */
class PayoutController extends Controller
{
    private function companyId(Request $request): int
    {
        return (int) $request->user()->company_id;
    }

    private function guard(Request $request, ?PrizePayout $payout = null): void
    {
        abort_unless($request->user()->can('payouts.approve'), 403);
        if ($payout) {
            abort_unless($payout->company_id === $this->companyId($request), 403);
        }
    }

    public function index(Request $request): Response
    {
        $this->guard($request);

        $payouts = PrizePayout::where('company_id', $this->companyId($request))
            ->with('customer:id,name', 'result.chitty:id,code')
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PrizePayout $p) => [
                'id' => $p->ulid,
                'customer' => $p->customer?->name,
                'chitty' => $p->result?->chitty?->code,
                'amount' => (float) $p->amount,
                'status' => $p->status->value,
                'approvedAt' => $p->approved_at?->toIso8601String(),
                'paidAt' => $p->paid_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/payouts/index', [
            'payouts' => $payouts,
            'filters' => $request->only('status'),
        ]);
    }

    public function approve(Request $request, PrizePayout $payout): RedirectResponse
    {
        $this->guard($request, $payout);

        if ($payout->status !== PayoutStatus::Pending) {
            return back()->with('error', 'Only pending payouts can be approved.');
        }

        $payout->update([
            'status' => PayoutStatus::Approved->value,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        activity('payout')->performedOn($payout)->causedBy($request->user())->log('Prize payout approved');

        return back()->with('success', 'Payout approved.');
    }

    public function markPaid(Request $request, PrizePayout $payout): RedirectResponse
    {
        $this->guard($request, $payout);

        if ($payout->status !== PayoutStatus::Approved) {
            return back()->with('error', 'Only approved payouts can be marked paid.');
        }

        $validated = $request->validate(['reference' => ['nullable', 'string', 'max:100']]);

        $payout->update([
            'status' => PayoutStatus::Paid->value,
            'paid_at' => now(),
            'reference' => $validated['reference'] ?? null,
        ]);

        activity('payout')->performedOn($payout)->causedBy($request->user())->log('Prize payout marked paid');

        return back()->with('success', 'Payout marked as paid.');
    }

    public function reject(Request $request, PrizePayout $payout): RedirectResponse
    {
        $this->guard($request, $payout);
        abort_if($payout->status === PayoutStatus::Paid, 403, 'Paid payouts cannot be rejected.');

        $payout->update(['status' => PayoutStatus::Rejected->value]);
        activity('payout')->performedOn($payout)->causedBy($request->user())->log('Prize payout rejected');

        return back()->with('success', 'Payout rejected.');
    }
}

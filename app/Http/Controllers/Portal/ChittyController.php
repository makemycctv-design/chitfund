<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ChittyMembership;
use App\Models\InstallmentPayment;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Customer's view of their own chitties and per-chitty installment ledger.
 * All queries are constrained to the authenticated customer's own records.
 */
class ChittyController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $memberships = ChittyMembership::where('customer_id', $user->id)
            ->with('chitty:id,ulid,code,name,installment_amount,duration_months,status,maturity_date')
            ->get()
            ->map(fn (ChittyMembership $m) => [
                'id' => $m->chitty?->ulid,
                'code' => $m->chitty?->code,
                'name' => $m->chitty?->name,
                'ticketNumber' => $m->ticket_number,
                'installmentAmount' => (float) ($m->chitty?->installment_amount ?? 0),
                'durationMonths' => $m->chitty?->duration_months,
                'status' => $m->status->value,
                'chittyStatus' => $m->chitty?->status->value,
                'isPrized' => $m->is_prized,
            ]);

        return Inertia::render('portal/chitties/index', ['chitties' => $memberships]);
    }

    public function show(Request $request, string $chitty): Response
    {
        $user = $request->user();

        // Resolve via the customer's own membership so cross-customer access is impossible.
        $membership = ChittyMembership::where('customer_id', $user->id)
            ->whereHas('chitty', fn ($q) => $q->where('ulid', $chitty))
            ->with('chitty')
            ->firstOrFail();

        $installments = InstallmentPayment::where('chitty_membership_id', $membership->id)
            ->orderBy('period_no')
            ->get()
            ->map(fn (InstallmentPayment $p) => [
                'id' => $p->ulid,
                'periodNo' => $p->period_no,
                'dueDate' => $p->due_date?->toDateString(),
                'amountDue' => (float) $p->amount_due,
                'lateFee' => (float) $p->late_fee,
                'amountPaid' => (float) $p->amount_paid,
                'outstanding' => (float) $p->outstanding(),
                'status' => $p->status->value,
                'paidAt' => $p->paid_at?->toIso8601String(),
                'isPayable' => in_array($p->status->value, ['pending', 'partial', 'overdue'], true),
            ]);

        $totalOutstanding = $installments->reduce(
            fn (string $c, $p) => Money::add($c, (string) $p['outstanding']), '0.00'
        );

        $c = $membership->chitty;

        return Inertia::render('portal/chitties/show', [
            'chitty' => [
                'id' => $c->ulid,
                'code' => $c->code,
                'name' => $c->name,
                'installmentAmount' => (float) $c->installment_amount,
                'durationMonths' => $c->duration_months,
                'status' => $c->status->value,
                'ticketNumber' => $membership->ticket_number,
                'maturityDate' => $c->maturity_date?->toDateString(),
            ],
            'installments' => $installments,
            'totalOutstanding' => (float) $totalOutstanding,
        ]);
    }
}

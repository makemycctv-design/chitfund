<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReconciliationController extends Controller
{
    private function companyId(Request $request): int
    {
        return (int) $request->user()->company_id;
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('reconciliation.approve'), 403);

        $transactions = PaymentTransaction::query()
            ->where('company_id', $this->companyId($request))
            ->with(['customer:id,ulid,name', 'chitty:id,code'])
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->when($request->string('gateway')->toString(), fn ($q, $g) => $q->where('gateway', $g))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PaymentTransaction $t) => [
                'id' => $t->ulid,
                'reference' => $t->reference,
                'customer' => $t->customer?->name,
                'chitty' => $t->chitty?->code,
                'gateway' => $t->gateway,
                'method' => $t->method?->value,
                'amount' => (float) $t->amount,
                'status' => $t->status->value,
                'reconciled' => $t->reconciled_at !== null,
                'createdAt' => $t->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/reconciliation/index', [
            'transactions' => $transactions,
            'filters' => $request->only(['status', 'gateway']),
        ]);
    }

    public function approve(Request $request, PaymentTransaction $transaction): RedirectResponse
    {
        abort_unless($request->user()->can('reconciliation.approve'), 403);
        abort_unless($transaction->company_id === $this->companyId($request), 403);

        if ($transaction->status !== PaymentStatus::Success) {
            return back()->with('error', 'Only successful transactions can be reconciled.');
        }

        $transaction->update([
            'reconciled_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        activity('payment')->performedOn($transaction)->causedBy($request->user())
            ->log('Transaction reconciled');

        return back()->with('success', 'Transaction reconciled.');
    }
}

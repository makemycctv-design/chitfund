<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Payments\RecordManualPayment;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\RecordCollectionRequest;
use App\Models\InstallmentPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    private function companyId(Request $request): int
    {
        return (int) $request->user()->company_id;
    }

    /** Outstanding installments queue for collection staff. */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('collections.record'), 403);

        $items = InstallmentPayment::query()
            ->where('company_id', $this->companyId($request))
            ->outstanding()
            ->with(['customer:id,ulid,name,phone', 'chitty:id,code,name'])
            ->when($request->boolean('overdue_only'), fn ($q) => $q->whereDate('due_date', '<', now()))
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->whereHas(
                'customer', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")
            ))
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (InstallmentPayment $p) => [
                'id' => $p->ulid,
                'customerName' => $p->customer?->name,
                'customerPhone' => $p->customer?->phone,
                'chitty' => $p->chitty?->code,
                'periodNo' => $p->period_no,
                'dueDate' => $p->due_date?->toDateString(),
                'amountDue' => (float) $p->amount_due,
                'lateFee' => (float) $p->late_fee,
                'outstanding' => (float) $p->outstanding(),
                'status' => $p->status->value,
                'isOverdue' => $p->due_date?->isPast() && $p->status->value !== 'paid',
            ]);

        return Inertia::render('admin/collections/index', [
            'installments' => $items,
            'filters' => $request->only(['search', 'overdue_only']),
            'methods' => array_map(
                fn (PaymentMethod $m) => ['value' => $m->value, 'label' => $m->label()],
                array_map(fn ($v) => PaymentMethod::from($v), PaymentMethod::manualMethods()),
            ),
        ]);
    }

    public function store(RecordCollectionRequest $request, RecordManualPayment $recorder): RedirectResponse
    {
        $installment = InstallmentPayment::where('ulid', $request->validated('installment_id'))
            ->where('company_id', $this->companyId($request))
            ->firstOrFail();

        $transaction = $recorder->handle(
            staff: $request->user(),
            installment: $installment,
            method: PaymentMethod::from($request->validated('method')),
            amount: (string) $request->validated('amount'),
            note: $request->validated('note'),
            idempotencyKey: $request->validated('idempotency_key'),
        );

        $installment->customer?->notify(new \App\Notifications\PaymentReceived($transaction->load('receipt', 'chitty')));

        return back()->with('success', 'Payment recorded and receipt issued.');
    }
}

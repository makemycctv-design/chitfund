<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Installments\GenerateInstallmentSchedule;
use App\Enums\ChittyStatus;
use App\Enums\KycStatus;
use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chitty\StoreChittyRequest;
use App\Http\Requests\Chitty\UpdateChittyRequest;
use App\Models\Branch;
use App\Models\Chitty;
use App\Models\ChittyScheme;
use App\Models\User;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ChittyController extends Controller
{
    /** All queries are scoped to the signed-in user's company. */
    private function companyId(Request $request): int
    {
        return (int) $request->user()->company_id;
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Chitty::class);

        $chitties = Chitty::query()
            ->where('company_id', $this->companyId($request))
            ->with('branch:id,name,code')
            ->withCount('memberships')
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%")
            ))
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->integer('branch_id'), fn ($q, $b) => $q->where('branch_id', $b))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Chitty $c) => [
                'id' => $c->ulid,
                'code' => $c->code,
                'name' => $c->name,
                'branch' => $c->branch?->name,
                'chitValue' => (float) $c->chit_value,
                'installmentAmount' => (float) $c->installment_amount,
                'durationMonths' => $c->duration_months,
                'subscribers' => $c->memberships_count,
                'totalSubscribers' => $c->total_subscribers,
                'status' => $c->status->value,
                'statusLabel' => $c->status->label(),
            ]);

        return Inertia::render('admin/chitties/index', [
            'chitties' => $chitties,
            'branches' => $this->branchOptions($request),
            'statuses' => $this->statusOptions(),
            'filters' => $request->only(['search', 'status', 'branch_id']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Chitty::class);

        return Inertia::render('admin/chitties/create', [
            'branches' => $this->branchOptions($request),
            'schemes' => $this->schemeOptions($request),
        ]);
    }

    public function store(StoreChittyRequest $request): RedirectResponse
    {
        $chitty = new Chitty($request->validated());
        $chitty->company_id = $this->companyId($request);
        $chitty->created_by = $request->user()->id;
        $chitty->status = ChittyStatus::Draft->value;
        $chitty->save();

        return redirect()
            ->route('admin.chitties.show', $chitty)
            ->with('success', "Chitty {$chitty->code} created.");
    }

    public function show(Request $request, Chitty $chitty): Response
    {
        $this->authorize('view', $chitty);

        $chitty->load(['branch:id,name,code', 'scheme:id,name,code']);

        $memberships = $chitty->memberships()
            ->with('customer:id,ulid,name,email,phone')
            ->orderBy('ticket_number')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->ulid,
                'ticketNumber' => $m->ticket_number,
                'customerName' => $m->customer?->name,
                'customerEmail' => $m->customer?->email,
                'status' => $m->status->value,
                'isPrized' => $m->is_prized,
            ]);

        // Ledger summary from installment obligations (real figures).
        $payments = $chitty->installmentPayments();
        $collected = (string) $chitty->installmentPayments()->sum('amount_paid');
        $due = (string) $chitty->installmentPayments()->sum('amount_due');
        $lateFees = (string) $chitty->installmentPayments()->sum('late_fee');
        $outstanding = Money::sub(Money::add($due, $lateFees), $collected);
        $overdueCount = (clone $payments)->overdue()->count();

        return Inertia::render('admin/chitties/show', [
            'chitty' => [
                'id' => $chitty->ulid,
                'code' => $chitty->code,
                'name' => $chitty->name,
                'branch' => $chitty->branch?->name,
                'scheme' => $chitty->scheme?->name,
                'chitValue' => (float) $chitty->chit_value,
                'installmentAmount' => (float) $chitty->installment_amount,
                'durationMonths' => $chitty->duration_months,
                'totalSubscribers' => $chitty->total_subscribers,
                'foremanCommissionPercent' => (float) $chitty->foreman_commission_percent,
                'minBidPercent' => $chitty->min_bid_percent !== null ? (float) $chitty->min_bid_percent : null,
                'maxBidPercent' => $chitty->max_bid_percent !== null ? (float) $chitty->max_bid_percent : null,
                'gracePeriodDays' => $chitty->grace_period_days,
                'startDate' => $chitty->start_date?->toDateString(),
                'maturityDate' => $chitty->maturity_date?->toDateString(),
                'auctionDay' => $chitty->auction_day,
                'status' => $chitty->status->value,
                'statusLabel' => $chitty->status->label(),
                'hasSchedule' => $chitty->hasSchedule(),
                'availableSlots' => $chitty->availableSlots(),
                'notes' => $chitty->notes,
                'terms' => $chitty->terms,
            ],
            'memberships' => $memberships,
            'ledger' => [
                'collected' => (float) $collected,
                'due' => (float) Money::add($due, $lateFees),
                'outstanding' => (float) (Money::isNegative($outstanding) ? '0.00' : $outstanding),
                'overdueCount' => $overdueCount,
            ],
            'allowedTransitions' => array_map(
                fn (ChittyStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                $chitty->status->allowedTransitions(),
            ),
            'eligibleCustomers' => $this->eligibleCustomers($request, $chitty),
        ]);
    }

    public function edit(Request $request, Chitty $chitty): Response
    {
        $this->authorize('update', $chitty);

        return Inertia::render('admin/chitties/edit', [
            'chitty' => array_merge(
                $chitty->only([
                    'code', 'name', 'branch_id', 'chitty_scheme_id', 'chit_value',
                    'duration_months', 'total_subscribers', 'installment_amount',
                    'foreman_commission_percent', 'auction_frequency', 'min_bid_percent',
                    'max_bid_percent', 'grace_period_days', 'late_fee_type', 'late_fee_value',
                    'required_kyc_level', 'auction_day', 'notes', 'terms',
                ]),
                [
                    'id' => $chitty->ulid,
                    'enrollment_opens_on' => $chitty->enrollment_opens_on?->toDateString(),
                    'enrollment_closes_on' => $chitty->enrollment_closes_on?->toDateString(),
                    'start_date' => $chitty->start_date?->toDateString(),
                    'maturity_date' => $chitty->maturity_date?->toDateString(),
                    'auction_time' => $chitty->auction_time,
                ],
            ),
            'branches' => $this->branchOptions($request),
            'schemes' => $this->schemeOptions($request),
        ]);
    }

    public function update(UpdateChittyRequest $request, Chitty $chitty): RedirectResponse
    {
        $chitty->update($request->validated());

        return redirect()
            ->route('admin.chitties.show', $chitty)
            ->with('success', 'Chitty updated.');
    }

    /** Transition the chitty to a new lifecycle status (validated by the state machine). */
    public function transition(Request $request, Chitty $chitty): RedirectResponse
    {
        $this->authorize('update', $chitty);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ChittyStatus::class)],
        ]);

        $target = ChittyStatus::from($validated['status']);

        if (! $chitty->status->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move from {$chitty->status->label()} to {$target->label()}.",
            ]);
        }

        $chitty->update(['status' => $target->value]);

        activity('chitty')->performedOn($chitty)->causedBy($request->user())
            ->log("Chitty status changed to {$target->label()}");

        return back()->with('success', "Status updated to {$target->label()}.");
    }

    /** Enroll an eligible (KYC-verified) customer into an open slot. */
    public function enroll(Request $request, Chitty $chitty): RedirectResponse
    {
        $this->authorize('update', $chitty);
        abort_unless($request->user()->can('members.enroll'), 403);

        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('users', 'ulid')->where('company_id', $companyId)->where('type', 'customer'),
            ],
        ]);

        if ($chitty->availableSlots() <= 0) {
            throw ValidationException::withMessages(['customer_id' => 'This chitty is full.']);
        }

        $customer = User::where('ulid', $validated['customer_id'])->firstOrFail();

        if ($chitty->memberships()->where('customer_id', $customer->id)->exists()) {
            throw ValidationException::withMessages(['customer_id' => 'Customer is already enrolled.']);
        }

        if ($customer->customerProfile?->kyc_status !== KycStatus::Verified) {
            throw ValidationException::withMessages(['customer_id' => 'Customer KYC is not verified.']);
        }

        $nextTicket = (int) $chitty->memberships()->max('ticket_number') + 1;

        $chitty->memberships()->create([
            'company_id' => $companyId,
            'customer_id' => $customer->id,
            'ticket_number' => $nextTicket,
            'status' => MembershipStatus::Active->value,
            'joined_on' => now(),
        ]);

        return back()->with('success', "{$customer->name} enrolled (ticket #{$nextTicket}).");
    }

    /** Generate the full installment schedule for this chitty. */
    public function generateSchedule(Request $request, Chitty $chitty, GenerateInstallmentSchedule $generator): RedirectResponse
    {
        $this->authorize('update', $chitty);
        abort_unless($request->user()->can('installments.generate'), 403);

        try {
            $result = $generator->handle($chitty);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        activity('chitty')->performedOn($chitty)->causedBy($request->user())
            ->log('Installment schedule generated');

        return back()->with('success', "Schedule generated: {$result['periods']} periods, {$result['obligations']} obligations.");
    }

    // ----- option helpers --------------------------------------------------

    private function branchOptions(Request $request): array
    {
        return Branch::where('company_id', $this->companyId($request))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($b) => ['value' => $b->id, 'label' => "{$b->name} ({$b->code})"])
            ->all();
    }

    private function schemeOptions(Request $request): array
    {
        return ChittyScheme::where('company_id', $this->companyId($request))
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (ChittyScheme $s) => [
                'value' => $s->id,
                'label' => "{$s->name} ({$s->code})",
                'defaults' => [
                    'chit_value' => (float) $s->chit_value,
                    'duration_months' => $s->duration_months,
                    'total_subscribers' => $s->total_subscribers,
                    'installment_amount' => (float) $s->installment_amount,
                    'foreman_commission_percent' => (float) $s->foreman_commission_percent,
                    'auction_frequency' => $s->auction_frequency,
                    'min_bid_percent' => $s->min_bid_percent !== null ? (float) $s->min_bid_percent : null,
                    'max_bid_percent' => $s->max_bid_percent !== null ? (float) $s->max_bid_percent : null,
                    'grace_period_days' => $s->grace_period_days,
                    'late_fee_type' => $s->late_fee_type,
                    'late_fee_value' => (float) $s->late_fee_value,
                    'required_kyc_level' => $s->required_kyc_level,
                ],
            ])->all();
    }

    private function statusOptions(): array
    {
        return array_map(
            fn (ChittyStatus $s) => ['value' => $s->value, 'label' => $s->label()],
            ChittyStatus::cases(),
        );
    }

    private function eligibleCustomers(Request $request, Chitty $chitty): array
    {
        $enrolledIds = $chitty->memberships()->pluck('customer_id');

        return User::where('company_id', $this->companyId($request))
            ->where('type', 'customer')
            ->whereNotIn('id', $enrolledIds)
            ->whereHas('customerProfile', fn ($q) => $q->where('kyc_status', KycStatus::Verified->value))
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'ulid', 'name', 'email'])
            ->map(fn ($u) => ['value' => $u->ulid, 'label' => "{$u->name} ({$u->email})"])
            ->all();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KycStatus;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    private function companyId(Request $request): int
    {
        return (int) $request->user()->company_id;
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CustomerProfile::class);

        $branchScope = $request->user()->branchScopeId();

        $customers = User::query()
            ->where('company_id', $this->companyId($request))
            ->where('type', 'customer')
            // Branch-scoped staff only see customers belonging to their branch.
            ->when($branchScope !== null, fn ($q) => $q->where('branch_id', $branchScope))
            ->with('customerProfile:id,user_id,customer_code,registration_status,kyc_status,kyc_level')
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
            ))
            ->when($request->string('registration')->toString(), fn ($q, $r) => $q->whereHas(
                'customerProfile', fn ($q) => $q->where('registration_status', $r)
            ))
            ->when($request->string('kyc')->toString(), fn ($q, $k) => $q->whereHas(
                'customerProfile', fn ($q) => $q->where('kyc_status', $k)
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->ulid,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'customerCode' => $u->customerProfile?->customer_code,
                'registrationStatus' => $u->customerProfile?->registration_status?->value,
                'kycStatus' => $u->customerProfile?->kyc_status?->value,
            ]);

        return Inertia::render('admin/customers/index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'registration', 'kyc']),
        ]);
    }

    public function show(Request $request, User $customer): Response
    {
        $profile = $customer->customerProfile()->firstOrFail();
        $this->authorize('view', $profile);

        $customer->load([
            'kycDocuments' => fn ($q) => $q->latest(),
            'memberships.chitty:id,ulid,code,name,status',
        ]);

        return Inertia::render('admin/customers/show', [
            'customer' => [
                'id' => $customer->ulid,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'isActive' => $customer->is_active,
            ],
            'profile' => [
                'customerCode' => $profile->customer_code,
                'registrationStatus' => $profile->registration_status->value,
                'kycStatus' => $profile->kyc_status->value,
                'kycLevel' => $profile->kyc_level,
                'occupation' => $profile->occupation,
                'city' => $profile->city,
                'state' => $profile->state,
                'rejectionReason' => $profile->rejection_reason,
            ],
            'documents' => $customer->kycDocuments->map(fn ($d) => [
                'id' => $d->ulid,
                'type' => $d->type->value,
                'typeLabel' => $d->type->label(),
                'originalName' => $d->original_name,
                'status' => $d->status->value,
                'rejectionReason' => $d->rejection_reason,
                'uploadedAt' => $d->created_at?->toIso8601String(),
            ]),
            'memberships' => $customer->memberships->map(fn ($m) => [
                'chittyCode' => $m->chitty?->code,
                'chittyName' => $m->chitty?->name,
                'ticketNumber' => $m->ticket_number,
                'status' => $m->status->value,
            ]),
            'can' => [
                'approveRegistration' => $request->user()->can('approveRegistration', $profile),
                'verifyKyc' => $request->user()->can('verifyKyc', $profile),
            ],
        ]);
    }

    public function approveRegistration(Request $request, User $customer): RedirectResponse
    {
        $profile = $customer->customerProfile()->firstOrFail();
        $this->authorize('approveRegistration', $profile);

        $profile->update([
            'registration_status' => RegistrationStatus::Approved->value,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        activity('customer')->performedOn($profile)->causedBy($request->user())
            ->log('Customer registration approved');

        $customer->notify(new \App\Notifications\WelcomeCustomer());

        return back()->with('success', 'Registration approved.');
    }

    public function rejectRegistration(Request $request, User $customer): RedirectResponse
    {
        $profile = $customer->customerProfile()->firstOrFail();
        $this->authorize('approveRegistration', $profile);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $profile->update([
            'registration_status' => RegistrationStatus::Rejected->value,
            'rejection_reason' => $validated['reason'],
        ]);

        activity('customer')->performedOn($profile)->causedBy($request->user())
            ->log('Customer registration rejected');

        return back()->with('success', 'Registration rejected.');
    }

    /** Mark the customer's overall KYC as verified (after reviewing documents). */
    public function verifyKyc(Request $request, User $customer): RedirectResponse
    {
        $profile = $customer->customerProfile()->firstOrFail();
        $this->authorize('verifyKyc', $profile);

        $profile->update([
            'kyc_status' => KycStatus::Verified->value,
            'kyc_level' => max(1, $profile->kyc_level),
            'kyc_verified_at' => now(),
        ]);

        activity('customer')->performedOn($profile)->causedBy($request->user())
            ->log('Customer KYC verified');

        $customer->notify(new \App\Notifications\KycApproved());

        return back()->with('success', 'KYC verified.');
    }

    /** Soft-delete a customer (preserves financial/audit history). */
    public function destroy(Request $request, User $customer): RedirectResponse
    {
        abort_unless($request->user()->can('customers.manage'), 403);
        abort_unless(
            $customer->isCustomer() && $customer->company_id === (int) $request->user()->company_id,
            403,
        );

        // Branch-scoped staff may only remove customers of their own branch.
        $branchScope = $request->user()->branchScopeId();
        abort_unless($branchScope === null || $customer->branch_id === $branchScope, 403);

        // Guard: don't remove a customer who is actively enrolled in a chitty.
        if ($customer->memberships()->where('status', 'active')->exists()) {
            return back()->with('error', 'Cannot delete a customer with active chitty memberships.');
        }

        $customer->delete(); // soft delete (User uses SoftDeletes)

        activity('customer')->performedOn($customer)->causedBy($request->user())
            ->log('Customer deleted');

        return back()->with('success', 'Customer removed.');
    }
}

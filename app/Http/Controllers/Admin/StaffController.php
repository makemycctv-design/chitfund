<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\StaffProfile;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class StaffController extends Controller
{
    private function companyId(Request $request): int
    {
        return (int) $request->user()->company_id;
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('staff.view'), 403);

        $staff = User::where('company_id', $this->companyId($request))
            ->where('type', UserType::Staff->value)
            ->with('staffProfile:id,user_id,designation,status,branch_id')
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")
            ))
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->ulid,
                'name' => $u->name,
                'email' => $u->email,
                'roles' => $u->getRoleNames(),
                'designation' => $u->staffProfile?->designation,
                'status' => $u->staffProfile?->status ?? 'active',
                'isActive' => $u->is_active,
            ]);

        return Inertia::render('admin/staff/index', [
            'staff' => $staff,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->can('staff.manage'), 403);

        return Inertia::render('admin/staff/create', [
            'roles' => $this->assignableRoles(),
            'branches' => $this->branchOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('staff.manage'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in($this->assignableRoleSlugs())],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $this->companyId($request))],
            'designation' => ['nullable', 'string', 'max:255'],
        ]);

        $companyId = $this->companyId($request);

        $user = DB::transaction(function () use ($validated, $companyId, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'type' => UserType::Staff->value,
                'company_id' => $companyId,
                'branch_id' => $validated['branch_id'] ?? null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $user->assignRole($validated['role']);

            StaffProfile::create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'branch_id' => $validated['branch_id'] ?? null,
                'employee_code' => 'EMP-'.strtoupper(Str::random(6)),
                'designation' => $validated['designation'] ?? null,
                'joined_on' => now(),
                'status' => 'active',
            ]);

            activity('staff')->performedOn($user)->causedBy($request->user())->log('Staff account created');

            return $user;
        });

        return redirect()->route('admin.staff.show', $user)->with('success', 'Staff account created.');
    }

    public function show(Request $request, User $staff): Response
    {
        abort_unless($request->user()->can('staff.view'), 403);
        abort_unless($staff->company_id === $this->companyId($request) && $staff->isStaff(), 403);

        $staff->load('staffProfile.branch:id,name', 'loginHistories');

        return Inertia::render('admin/staff/show', [
            'staff' => [
                'id' => $staff->ulid,
                'name' => $staff->name,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'roles' => $staff->getRoleNames(),
                'designation' => $staff->staffProfile?->designation,
                'branch' => $staff->staffProfile?->branch?->name,
                'branchId' => $staff->staffProfile?->branch_id,
                'status' => $staff->staffProfile?->status ?? 'active',
                'lastLoginAt' => $staff->last_login_at?->toIso8601String(),
            ],
            'loginHistory' => $staff->loginHistories()->latest('logged_in_at')->limit(10)->get()
                ->map(fn ($l) => ['ip' => $l->ip_address, 'at' => $l->logged_in_at?->toIso8601String(), 'agent' => $l->user_agent]),
            'activity' => Activity::where('causer_id', $staff->id)->latest()->limit(15)->get()
                ->map(fn ($a) => ['description' => $a->description, 'at' => $a->created_at?->toIso8601String()]),
            'roles' => $this->assignableRoles(),
            'branches' => $this->branchOptions($request),
            'can' => ['manage' => $request->user()->can('staff.manage'), 'assignRoles' => $request->user()->can('roles.assign')],
        ]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        abort_unless($request->user()->can('staff.manage'), 403);
        abort_unless($staff->company_id === $this->companyId($request) && $staff->isStaff(), 403);

        $validated = $request->validate([
            'role' => ['required', Rule::in($this->assignableRoleSlugs())],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $this->companyId($request))],
            'designation' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->user()->can('roles.assign')) {
            $staff->syncRoles([$validated['role']]);
        }
        $staff->update(['branch_id' => $validated['branch_id'] ?? null]);
        $staff->staffProfile?->update([
            'branch_id' => $validated['branch_id'] ?? null,
            'designation' => $validated['designation'] ?? null,
        ]);

        activity('staff')->performedOn($staff)->causedBy($request->user())->log('Staff account updated');

        return back()->with('success', 'Staff updated.');
    }

    public function suspend(Request $request, User $staff): RedirectResponse
    {
        abort_unless($request->user()->can('staff.manage'), 403);
        abort_unless($staff->company_id === $this->companyId($request) && $staff->isStaff(), 403);
        abort_if($staff->id === $request->user()->id, 403, 'You cannot suspend yourself.');

        $staff->update(['is_active' => false]);
        $staff->staffProfile?->update(['status' => 'suspended']);
        activity('staff')->performedOn($staff)->causedBy($request->user())->log('Staff account suspended');

        return back()->with('success', 'Staff suspended.');
    }

    public function reactivate(Request $request, User $staff): RedirectResponse
    {
        abort_unless($request->user()->can('staff.manage'), 403);
        abort_unless($staff->company_id === $this->companyId($request) && $staff->isStaff(), 403);

        $staff->update(['is_active' => true]);
        $staff->staffProfile?->update(['status' => 'active']);
        activity('staff')->performedOn($staff)->causedBy($request->user())->log('Staff account reactivated');

        return back()->with('success', 'Staff reactivated.');
    }

    private function assignableRoleSlugs(): array
    {
        return array_values(array_diff(array_keys(Rbac::roleLabels()), [Rbac::SUPER_ADMIN, Rbac::CUSTOMER]));
    }

    private function assignableRoles(): array
    {
        $labels = Rbac::roleLabels();

        return array_map(fn ($slug) => ['value' => $slug, 'label' => $labels[$slug]], $this->assignableRoleSlugs());
    }

    private function branchOptions(Request $request): array
    {
        return Branch::where('company_id', $this->companyId($request))->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn ($b) => ['value' => $b->id, 'label' => "{$b->name} ({$b->code})"])->all();
    }
}

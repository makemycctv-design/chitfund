<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CustomerProfile;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        $company = Company::query()->orderBy('id')->first();

        // Offer the default company's active branches so the customer can pick
        // the branch that will handle their registration/approval.
        $branches = $company
            ? \App\Models\Branch::where('company_id', $company->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn ($b) => ['value' => $b->id, 'label' => "{$b->name} ({$b->code})"])
                ->all()
            : [];

        return Inertia::render('auth/register', [
            'branches' => $branches,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Public self-registration always creates a customer/subscriber attached
        // to the default (first) company. Registration begins in a "pending"
        // state and must be approved by that branch's staff before enrollment.
        $company = Company::query()->orderBy('id')->first();

        // The branch is required whenever the company has active branches, so
        // every new customer is owned by exactly one branch for approval.
        $hasBranches = $company
            && \App\Models\Branch::where('company_id', $company->id)->where('is_active', true)->exists();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone' => ['nullable', 'string', 'max:20', 'unique:'.User::class.',phone'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'branch_id' => [
                $hasBranches ? 'required' : 'nullable',
                \Illuminate\Validation\Rule::exists('branches', 'id')
                    ->where('company_id', $company?->id)
                    ->where('is_active', true),
            ],
        ]);

        $user = DB::transaction(function () use ($validated, $company) {
            $branchId = $validated['branch_id'] ?? null;

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'type' => UserType::Customer->value,
                'company_id' => $company?->id,
                'branch_id' => $branchId,
                'is_active' => true,
            ]);

            $user->assignRole(Rbac::CUSTOMER);

            if ($company) {
                CustomerProfile::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'branch_id' => $branchId,
                ]);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return to_route('portal.dashboard');
    }
}

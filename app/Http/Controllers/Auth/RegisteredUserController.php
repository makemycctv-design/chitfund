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
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone' => ['nullable', 'string', 'max:20', 'unique:'.User::class.',phone'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Public self-registration always creates a customer/subscriber attached
        // to the default (first) company. Registration begins in a "pending"
        // state and must be approved by staff before enrollment (Phase 2).
        $company = Company::query()->orderBy('id')->first();

        $user = DB::transaction(function () use ($validated, $company) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'type' => UserType::Customer->value,
                'company_id' => $company?->id,
                'is_active' => true,
            ]);

            $user->assignRole(Rbac::CUSTOMER);

            if ($company) {
                CustomerProfile::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                ]);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return to_route('portal.dashboard');
    }
}

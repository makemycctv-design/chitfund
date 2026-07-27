<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Token-based auth for the React Native app and integrations. Issues Sanctum
 * personal access tokens. Web sessions continue to use the Inertia flow.
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return ApiResponse::error('This account is inactive.', 403);
        }

        // Mobile app is customer-facing; keep staff tokens out of it for now.
        if (! $user->isCustomer()) {
            return ApiResponse::error('This account cannot use the mobile app.', 403);
        }

        $token = $user->createToken($credentials['device_name'])->plainTextToken;

        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        $user->load('customerProfile');

        return ApiResponse::success([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Logged in.');
    }

    /**
     * Public self-registration from the mobile app. Creates a customer attached
     * to the default company (mirrors the web flow) and returns a token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $company = \App\Models\Company::query()->orderBy('id')->first();

        $user = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $company) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
                'type' => \App\Enums\UserType::Customer->value,
                'company_id' => $company?->id,
                'is_active' => true,
            ]);
            $user->assignRole(\App\Support\Rbac::CUSTOMER);

            if ($company) {
                \App\Models\CustomerProfile::create(['user_id' => $user->id, 'company_id' => $company->id]);
            }

            return $user;
        });

        event(new \Illuminate\Auth\Events\Registered($user));

        $token = $user->createToken($validated['device_name'])->plainTextToken;
        $user->load('customerProfile');

        return ApiResponse::success([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Registered.', 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('customerProfile');

        return ApiResponse::success(new UserResource($user));
    }
}

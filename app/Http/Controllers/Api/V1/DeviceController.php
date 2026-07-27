<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Registers/unregisters a device's push token so the PushChannel can deliver
 * push notifications to the mobile app.
 */
class DeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', Rule::in(['ios', 'android', 'web'])],
        ]);

        $device = $request->user()->deviceTokens()->updateOrCreate(
            ['token' => $validated['token']],
            ['platform' => $validated['platform'] ?? null, 'last_used_at' => now()],
        );

        return ApiResponse::success(['id' => $device->id], 'Device registered.');
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate(['token' => ['required', 'string']]);

        $request->user()->deviceTokens()->where('token', $validated['token'])->delete();

        return ApiResponse::success(null, 'Device unregistered.');
    }
}

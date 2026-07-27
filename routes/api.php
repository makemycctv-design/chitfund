<?php

use App\Http\Controllers\Api\V1\AuctionController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChittyController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\InstallmentController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
 * Versioned REST API for the React Native mobile app and future integrations.
 * All routes live under /api/v1. Web dashboard operations use Inertia, not this
 * API.
 */
Route::prefix('v1')->name('api.v1.')->group(function () {

    Route::get('/health', fn () => ApiResponse::success(['status' => 'ok']))->name('health');

    // Public auth endpoints (rate-limited to blunt credential stuffing).
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
    });

    // Gateway webhooks — public, authenticated by provider signature.
    Route::post('/webhooks/{gateway}', [WebhookController::class, 'handle'])
        ->middleware('throttle:60,1')
        ->name('webhooks.handle');

    // Authenticated customer endpoints.
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/profile', [AuthController::class, 'me'])->name('profile.me');

        // Push device registration
        Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::delete('/devices', [DeviceController::class, 'destroy'])->name('devices.destroy');

        Route::get('/chitties', [ChittyController::class, 'index'])->name('chitties.index');
        Route::get('/chitties/{chitty}', [ChittyController::class, 'show'])->name('chitties.show');

        Route::get('/installments', [InstallmentController::class, 'index'])->name('installments.index');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/initiate', [PaymentController::class, 'initiate'])
            ->middleware('throttle:20,1')
            ->name('payments.initiate');

        Route::get('/auctions', [AuctionController::class, 'index'])->name('auctions.index');
        Route::get('/auctions/{auction}', [AuctionController::class, 'show'])->name('auctions.show');
        Route::post('/auctions/{auction}/bid', [AuctionController::class, 'bid'])
            ->middleware('throttle:30,1')
            ->name('auctions.bid');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    });
});

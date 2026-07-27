<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Rbac;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Domain\Payments\PaymentGatewayManager::class);

        // WhatsApp provider: use Meta Cloud API when configured, else log driver.
        $this->app->bind(\App\Notifications\Providers\WhatsAppProvider::class, function () {
            if (config('services.whatsapp.token')) {
                return new \App\Notifications\Providers\MetaWhatsAppProvider(
                    config('services.whatsapp.token'),
                    config('services.whatsapp.phone_number_id'),
                );
            }

            return new \App\Notifications\Providers\LogWhatsAppProvider();
        });

        // Push provider: log driver until the mobile app registers device tokens.
        $this->app->bind(
            \App\Notifications\Providers\PushProvider::class,
            \App\Notifications\Providers\LogPushProvider::class,
        );
    }

    public function boot(): void
    {
        // Super Admin implicitly passes every authorization check. Returning
        // null for other users lets the normal policy/permission checks run.
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole(Rbac::SUPER_ADMIN) ? true : null;
        });

        // Delivery-status tracking is handled by App\Listeners\LogNotificationDelivery,
        // which Laravel auto-discovers from its typed handleSent/handleFailed methods.
    }
}

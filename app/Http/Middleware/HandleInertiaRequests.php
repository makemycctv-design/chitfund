<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props shared with every Inertia response: the authenticated user (with
     * roles/permissions for permission-aware UI), the active locale, the
     * translation dictionary for that locale, and light company branding.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $locale = $this->resolveLocale($request);
        App::setLocale($locale);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->ulid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'type' => $user->type?->value,
                    'locale' => $user->locale,
                    'avatar' => $user->avatar ?? null,
                    'email_verified_at' => $user->email_verified_at,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'company' => $user?->company ? [
                'name' => $user->company->name,
                'currency' => $user->company->currency,
                'timezone' => $user->company->timezone,
            ] : null,
            'notifications' => [
                'unread' => $user ? $user->unreadNotifications()->count() : 0,
            ],
            'locale' => $locale,
            'translations' => trans('messages'),
            'ziggy' => fn () => [
                'location' => $request->url(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Locale precedence: authenticated user's locale, then the company default,
     * then the app fallback. Structured to allow adding more languages easily.
     */
    private function resolveLocale(Request $request): string
    {
        $supported = ['en', 'ml'];

        $locale = $request->user()?->locale
            ?? $request->user()?->company?->locale
            ?? config('app.locale');

        return in_array($locale, $supported, true) ? $locale : 'en';
    }
}

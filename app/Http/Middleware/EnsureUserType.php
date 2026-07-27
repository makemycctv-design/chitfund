<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the two audiences apart: staff may only reach the admin area and
 * customers may only reach the portal. A mismatch redirects the user to their
 * own home instead of leaking the existence of the other surface.
 */
class EnsureUserType
{
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = $request->user();

        if (! $user || $user->type !== UserType::from($type)) {
            if ($user) {
                return redirect()->route($user->homeRoute());
            }

            abort(403);
        }

        return $next($request);
    }
}

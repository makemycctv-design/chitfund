import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';

/**
 * Permission-aware UI helpers. These mirror the server-side Spatie checks and
 * are used ONLY to show/hide UI affordances — real authorization is always
 * enforced in Laravel policies/middleware, never by hiding a button.
 */
export function usePermissions() {
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;

    const permissions = user?.permissions ?? [];
    const roles = user?.roles ?? [];

    // Super Admin implicitly has every permission (matches Gate::before).
    const isSuperAdmin = roles.includes('super-admin');

    const can = (permission: string) => isSuperAdmin || permissions.includes(permission);
    const hasRole = (role: string) => roles.includes(role);
    const canAny = (list: string[]) => list.some((p) => can(p));

    return { user, permissions, roles, isSuperAdmin, can, canAny, hasRole };
}

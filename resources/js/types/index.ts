import { LucideIcon } from 'lucide-react';

export type UserType = 'staff' | 'customer';

export interface Auth {
    /**
     * The authenticated user. On public pages (welcome/auth) this is null at
     * runtime; components inside authenticated layouts can rely on it existing.
     */
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    /** Permission required to see this item. Omit to always show. */
    permission?: string;
    /** Rendered muted with a "Soon" badge — feature arrives in a later phase. */
    disabled?: boolean;
}

export interface Company {
    name: string;
    currency: string;
    timezone: string;
}

/** Nested translation dictionary shared from the backend lang files. */
export type Translations = Record<string, unknown>;

export interface SharedData {
    name: string;
    quote?: { message: string; author: string };
    auth: Auth;
    company: Company | null;
    locale: string;
    translations: Translations;
    notifications: { unread: number };
    flash: { success?: string | null; error?: string | null };
    sidebarOpen?: boolean;
    ziggy: { location: string };
    [key: string]: unknown;
}

export interface User {
    id: string;
    name: string;
    email: string;
    phone?: string | null;
    type: UserType;
    locale: string;
    avatar?: string;
    email_verified_at: string | null;
    roles: string[];
    permissions: string[];
    [key: string]: unknown;
}

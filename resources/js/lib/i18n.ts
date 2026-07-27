import { usePage } from '@inertiajs/react';
import type { SharedData, Translations } from '@/types';

/**
 * Resolve a dot-notated key (e.g. "admin_dashboard.title") against a nested
 * translation dictionary. Returns the fallback (or the key itself) when the
 * path is missing, so a missing translation is visible but never fatal.
 */
export function translate(
    dictionary: Translations,
    key: string,
    fallback?: string,
): string {
    const value = key.split('.').reduce<unknown>((acc, part) => {
        if (acc && typeof acc === 'object' && part in (acc as Record<string, unknown>)) {
            return (acc as Record<string, unknown>)[part];
        }
        return undefined;
    }, dictionary);

    return typeof value === 'string' ? value : (fallback ?? key);
}

/**
 * Hook exposing a `t()` function bound to the current locale's dictionary that
 * the backend shares on every Inertia response.
 */
export function useTranslations() {
    const { translations, locale } = usePage<SharedData>().props;

    const t = (key: string, fallback?: string) =>
        translate(translations ?? {}, key, fallback);

    return { t, locale };
}

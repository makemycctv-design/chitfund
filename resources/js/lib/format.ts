/**
 * Locale-aware formatting helpers. Currency defaults to INR with the Indian
 * digit grouping (lakh/crore) and dates render in the Asia/Kolkata style used
 * across the app. Amounts are always passed as major-unit numbers (rupees).
 */

export function formatCurrency(
    amount: number | string | null | undefined,
    currency = 'INR',
    locale = 'en-IN',
): string {
    const value = typeof amount === 'string' ? parseFloat(amount) : (amount ?? 0);
    if (Number.isNaN(value)) return '—';

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(value);
}

/** Compact INR (e.g. ₹12.5L, ₹1.2Cr) for dense dashboard tiles. */
export function formatCurrencyCompact(
    amount: number | string | null | undefined,
    currency = 'INR',
): string {
    const value = typeof amount === 'string' ? parseFloat(amount) : (amount ?? 0);
    if (Number.isNaN(value)) return '—';

    if (value >= 10000000) return `₹${(value / 10000000).toFixed(2)}Cr`;
    if (value >= 100000) return `₹${(value / 100000).toFixed(2)}L`;
    return formatCurrency(value, currency);
}

export function formatDate(
    date: string | Date | null | undefined,
    locale = 'en-IN',
): string {
    if (!date) return '—';
    const d = typeof date === 'string' ? new Date(date) : date;
    if (Number.isNaN(d.getTime())) return '—';

    return new Intl.DateTimeFormat(locale, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(d);
}

export function formatDateTime(
    date: string | Date | null | undefined,
    locale = 'en-IN',
): string {
    if (!date) return '—';
    const d = typeof date === 'string' ? new Date(date) : date;
    if (Number.isNaN(d.getTime())) return '—';

    return new Intl.DateTimeFormat(locale, {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(d);
}

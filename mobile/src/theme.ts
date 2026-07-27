/**
 * Shared design tokens mirroring the web app's fintech palette so both
 * surfaces feel like one product.
 */
export const colors = {
    primary: '#2563eb',
    primaryText: '#ffffff',
    background: '#f8fafc',
    card: '#ffffff',
    border: '#e2e8f0',
    text: '#0f172a',
    muted: '#64748b',
    success: '#059669',
    danger: '#dc2626',
    warning: '#d97706',
};

export const spacing = { xs: 4, sm: 8, md: 12, lg: 16, xl: 24 };
export const radius = { sm: 6, md: 10, lg: 14 };

/** Currency + date formatting consistent with the web (INR, Indian style). */
export function formatCurrency(amount: number | string | null | undefined): string {
    const value = typeof amount === 'string' ? parseFloat(amount) : (amount ?? 0);
    if (Number.isNaN(value)) return '—';
    return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 2 }).format(value);
}

export function formatDate(date: string | null | undefined): string {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return new Intl.DateTimeFormat('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
}

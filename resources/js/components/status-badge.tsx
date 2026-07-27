import { Badge } from '@/components/ui/badge';

type Variant = 'default' | 'secondary' | 'destructive' | 'outline';

/**
 * Maps domain statuses to badge variants for consistent status rendering.
 */
const MAP: Record<string, Variant> = {
    // positive / settled
    active: 'default',
    approved: 'default',
    verified: 'default',
    paid: 'default',
    success: 'default',
    // in-progress / neutral
    pending: 'secondary',
    partial: 'secondary',
    submitted: 'secondary',
    initiated: 'secondary',
    draft: 'secondary',
    open_for_enrollment: 'secondary',
    auction_scheduled: 'secondary',
    auction_running: 'secondary',
    collecting: 'secondary',
    // negative
    rejected: 'destructive',
    overdue: 'destructive',
    failed: 'destructive',
    disputed: 'destructive',
    defaulted: 'destructive',
    cancelled: 'destructive',
};

export function StatusBadge({ status, label }: { status: string | null | undefined; label?: string }) {
    if (!status) return <span className="text-muted-foreground">—</span>;
    const variant = MAP[status] ?? 'outline';
    const text = label ?? status.replace(/_/g, ' ');

    return (
        <Badge variant={variant} className="capitalize">
            {text}
        </Badge>
    );
}

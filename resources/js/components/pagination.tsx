import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types/pagination';

/**
 * Renders Laravel paginator links. Preserves the current query string because
 * the backend uses ->withQueryString(). Falls back to nothing on a single page.
 */
export function Pagination({ links, from, to, total }: { links: PaginationLink[]; from: number | null; to: number | null; total: number }) {
    if (links.length <= 3) return null;

    return (
        <div className="flex flex-col items-center justify-between gap-3 pt-4 sm:flex-row">
            <p className="text-xs text-muted-foreground">
                Showing {from ?? 0}–{to ?? 0} of {total}
            </p>
            <div className="flex flex-wrap gap-1">
                {links.map((link, i) => (
                    <Link
                        key={i}
                        href={link.url ?? '#'}
                        preserveScroll
                        className={cn(
                            'min-w-8 rounded-md border px-2.5 py-1 text-center text-sm',
                            link.active ? 'border-primary bg-primary text-primary-foreground' : 'border-border hover:bg-muted',
                            !link.url && 'pointer-events-none opacity-50',
                        )}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ))}
            </div>
        </div>
    );
}

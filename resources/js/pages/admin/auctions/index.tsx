import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency, formatDateTime } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';

interface AuctionRow {
    id: string;
    chitty: string | null;
    chittyName: string | null;
    periodNo: number;
    status: string;
    scheduledAt: string | null;
    endsAt: string | null;
    prizeAmount: number | null;
}

interface Props {
    auctions: Paginated<AuctionRow>;
    filters: { status?: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Auctions', href: '/admin/auctions' }];

export default function AuctionsIndex({ auctions, filters }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Auctions" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Auctions</h1>
                    <Button asChild>
                        <Link href="/admin/auctions/create">
                            <Plus className="mr-1 h-4 w-4" /> Schedule Auction
                        </Link>
                    </Button>
                </div>
                <Card>
                    <CardContent className="pt-6">
                        <div className="mb-4 flex gap-2">
                            <select
                                value={filters.status ?? ''}
                                onChange={(e) => router.get('/admin/auctions', { status: e.target.value }, { preserveState: true, replace: true })}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">All statuses</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="live">Live</option>
                                <option value="paused">Paused</option>
                                <option value="closed">Closed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">Chitty</th>
                                        <th className="py-2 pr-4 font-medium">Period</th>
                                        <th className="py-2 pr-4 font-medium">Scheduled</th>
                                        <th className="py-2 pr-4 font-medium">Status</th>
                                        <th className="py-2 pr-4 font-medium">Prize</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {auctions.data.length === 0 ? (
                                        <tr><td colSpan={5} className="py-8 text-center text-muted-foreground">No auctions yet.</td></tr>
                                    ) : (
                                        auctions.data.map((a) => (
                                            <tr key={a.id} className="border-b last:border-0 hover:bg-muted/40">
                                                <td className="py-3 pr-4">
                                                    <Link href={`/admin/auctions/${a.id}`} className="font-medium hover:underline">{a.chittyName}</Link>
                                                    <div className="text-xs text-muted-foreground">{a.chitty}</div>
                                                </td>
                                                <td className="py-3 pr-4">#{a.periodNo}</td>
                                                <td className="py-3 pr-4 text-xs">{formatDateTime(a.scheduledAt)}</td>
                                                <td className="py-3 pr-4"><StatusBadge status={a.status} /></td>
                                                <td className="py-3 pr-4">{a.prizeAmount != null ? formatCurrency(a.prizeAmount) : '—'}</td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={auctions.links} from={auctions.from} to={auctions.to} total={auctions.total} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

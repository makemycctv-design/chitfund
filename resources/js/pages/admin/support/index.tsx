import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Card, CardContent } from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/react';

interface Ticket {
    id: string; reference: string; subject: string; customer: string | null;
    status: string; priority: string; lastMessageAt: string | null;
}
interface Props { tickets: Paginated<Ticket>; filters: { status?: string } }

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Support', href: '/admin/support' }];

export default function AdminSupportIndex({ tickets, filters }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Support" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Support Tickets</h1>
                <Card>
                    <CardContent className="pt-6">
                        <div className="mb-4">
                            <select value={filters.status ?? ''} onChange={(e) => router.get('/admin/support', { status: e.target.value }, { preserveState: true, replace: true })} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                <option value="">All statuses</option>
                                <option value="open">Open</option><option value="pending">Pending</option><option value="resolved">Resolved</option><option value="closed">Closed</option>
                            </select>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">Ref</th>
                                        <th className="py-2 pr-4 font-medium">Subject</th>
                                        <th className="py-2 pr-4 font-medium">Customer</th>
                                        <th className="py-2 pr-4 font-medium">Priority</th>
                                        <th className="py-2 pr-4 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {tickets.data.length === 0 ? (
                                        <tr><td colSpan={5} className="py-8 text-center text-muted-foreground">No tickets.</td></tr>
                                    ) : tickets.data.map((t) => (
                                        <tr key={t.id} className="border-b last:border-0 hover:bg-muted/40">
                                            <td className="py-2 pr-4 font-mono text-xs">{t.reference}</td>
                                            <td className="py-2 pr-4"><Link href={`/admin/support/${t.id}`} className="font-medium hover:underline">{t.subject}</Link></td>
                                            <td className="py-2 pr-4">{t.customer}</td>
                                            <td className="py-2 pr-4 capitalize">{t.priority}</td>
                                            <td className="py-2 pr-4"><StatusBadge status={t.status} /></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={tickets.links} from={tickets.from} to={tickets.to} total={tickets.total} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

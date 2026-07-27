import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency, formatDateTime } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/pagination';
import { Head, router } from '@inertiajs/react';

interface Payout {
    id: string; customer: string | null; chitty: string | null; amount: number;
    status: string; approvedAt: string | null; paidAt: string | null;
}
interface Props { payouts: Paginated<Payout>; filters: { status?: string } }

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Prize Payouts', href: '/admin/payouts' }];

export default function PayoutsIndex({ payouts, filters }: Props) {
    const act = (id: string, verb: string, data: Record<string, string> = {}) =>
        router.post(`/admin/payouts/${id}/${verb}`, data, { preserveScroll: true });
    const pay = (id: string) => {
        const reference = prompt('Payment reference (optional):') ?? '';
        act(id, 'pay', { reference });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Prize Payouts" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Prize Payouts</h1>
                <Card>
                    <CardContent className="pt-6">
                        <div className="mb-4">
                            <select value={filters.status ?? ''} onChange={(e) => router.get('/admin/payouts', { status: e.target.value }, { preserveState: true, replace: true })} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                <option value="">All statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="paid">Paid</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">Customer</th>
                                        <th className="py-2 pr-4 font-medium">Chitty</th>
                                        <th className="py-2 pr-4 font-medium">Amount</th>
                                        <th className="py-2 pr-4 font-medium">Status</th>
                                        <th className="py-2 pr-4 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {payouts.data.length === 0 ? (
                                        <tr><td colSpan={5} className="py-8 text-center text-muted-foreground">No payouts.</td></tr>
                                    ) : payouts.data.map((p) => (
                                        <tr key={p.id} className="border-b last:border-0">
                                            <td className="py-2 pr-4">{p.customer}</td>
                                            <td className="py-2 pr-4">{p.chitty}</td>
                                            <td className="py-2 pr-4 font-medium">{formatCurrency(p.amount)}</td>
                                            <td className="py-2 pr-4"><StatusBadge status={p.status} /></td>
                                            <td className="py-2 pr-4 text-right space-x-1">
                                                {p.status === 'pending' && <>
                                                    <Button size="sm" onClick={() => act(p.id, 'approve')}>Approve</Button>
                                                    <Button size="sm" variant="outline" onClick={() => act(p.id, 'reject')}>Reject</Button>
                                                </>}
                                                {p.status === 'approved' && <Button size="sm" variant="secondary" onClick={() => pay(p.id)}>Mark Paid</Button>}
                                                {p.status === 'paid' && <span className="text-xs text-muted-foreground">{formatDateTime(p.paidAt)}</span>}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={payouts.links} from={payouts.from} to={payouts.to} total={payouts.total} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

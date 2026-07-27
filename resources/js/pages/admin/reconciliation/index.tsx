import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency, formatDateTime } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/pagination';
import { Head, router } from '@inertiajs/react';

interface Txn {
    id: string;
    reference: string;
    customer: string | null;
    chitty: string | null;
    gateway: string;
    method: string | null;
    amount: number;
    status: string;
    reconciled: boolean;
    createdAt: string | null;
}

interface Props {
    transactions: Paginated<Txn>;
    filters: { status?: string; gateway?: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Reconciliation', href: '/admin/reconciliation' }];

export default function ReconciliationIndex({ transactions, filters }: Props) {
    const apply = (patch: Record<string, string>) =>
        router.get('/admin/reconciliation', { ...filters, ...patch }, { preserveState: true, replace: true });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reconciliation" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Payment Reconciliation</h1>
                <Card>
                    <CardContent className="pt-6">
                        <div className="mb-4 flex flex-wrap gap-2">
                            <select value={filters.status ?? ''} onChange={(e) => apply({ status: e.target.value })} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                <option value="">All statuses</option>
                                <option value="success">Success</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                            <select value={filters.gateway ?? ''} onChange={(e) => apply({ gateway: e.target.value })} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                <option value="">All gateways</option>
                                <option value="manual">Manual</option>
                                <option value="razorpay">Razorpay</option>
                            </select>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">Reference</th>
                                        <th className="py-2 pr-4 font-medium">Customer</th>
                                        <th className="py-2 pr-4 font-medium">Gateway</th>
                                        <th className="py-2 pr-4 font-medium">Amount</th>
                                        <th className="py-2 pr-4 font-medium">Status</th>
                                        <th className="py-2 pr-4 font-medium">Date</th>
                                        <th className="py-2 pr-4 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transactions.data.length === 0 ? (
                                        <tr><td colSpan={7} className="py-8 text-center text-muted-foreground">No transactions.</td></tr>
                                    ) : (
                                        transactions.data.map((t) => (
                                            <tr key={t.id} className="border-b last:border-0">
                                                <td className="py-2 pr-4 font-mono text-xs">{t.reference}</td>
                                                <td className="py-2 pr-4">{t.customer}</td>
                                                <td className="py-2 pr-4 capitalize">{t.gateway} / {t.method}</td>
                                                <td className="py-2 pr-4 font-medium">{formatCurrency(t.amount)}</td>
                                                <td className="py-2 pr-4"><StatusBadge status={t.status} /></td>
                                                <td className="py-2 pr-4 text-xs">{formatDateTime(t.createdAt)}</td>
                                                <td className="py-2 pr-4 text-right">
                                                    {t.status === 'success' && !t.reconciled ? (
                                                        <Button size="sm" variant="secondary" onClick={() => router.post(`/admin/reconciliation/${t.id}/approve`, {}, { preserveScroll: true })}>
                                                            Reconcile
                                                        </Button>
                                                    ) : t.reconciled ? (
                                                        <span className="text-xs text-emerald-600">Reconciled</span>
                                                    ) : null}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={transactions.links} from={transactions.from} to={transactions.to} total={transactions.total} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

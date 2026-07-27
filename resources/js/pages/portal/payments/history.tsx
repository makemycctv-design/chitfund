import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency, formatDateTime } from '@/lib/format';
import { useTranslations } from '@/lib/i18n';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/pagination';
import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';

interface Txn {
    id: string;
    reference: string;
    chitty: string | null;
    method: string | null;
    amount: number;
    status: string;
    receiptNumber: string | null;
    receiptId: string | null;
    createdAt: string | null;
}

export default function PaymentHistory({ transactions }: { transactions: Paginated<Txn> }) {
    const { t } = useTranslations();
    const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.payments'), href: '/portal/payments' }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('nav.payments')} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">{t('nav.payments')}</h1>
                <Card>
                    <CardContent className="pt-6">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">Reference</th>
                                        <th className="py-2 pr-4 font-medium">Chitty</th>
                                        <th className="py-2 pr-4 font-medium">Method</th>
                                        <th className="py-2 pr-4 font-medium">Amount</th>
                                        <th className="py-2 pr-4 font-medium">Status</th>
                                        <th className="py-2 pr-4 font-medium">Date</th>
                                        <th className="py-2 pr-4 font-medium">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transactions.data.length === 0 ? (
                                        <tr><td colSpan={7} className="py-8 text-center text-muted-foreground">No payments yet.</td></tr>
                                    ) : (
                                        transactions.data.map((tx) => (
                                            <tr key={tx.id} className="border-b last:border-0">
                                                <td className="py-2 pr-4 font-mono text-xs">{tx.reference}</td>
                                                <td className="py-2 pr-4">{tx.chitty ?? '—'}</td>
                                                <td className="py-2 pr-4 capitalize">{tx.method}</td>
                                                <td className="py-2 pr-4 font-medium">{formatCurrency(tx.amount)}</td>
                                                <td className="py-2 pr-4"><StatusBadge status={tx.status} /></td>
                                                <td className="py-2 pr-4 text-xs">{formatDateTime(tx.createdAt)}</td>
                                                <td className="py-2 pr-4">
                                                    {tx.receiptId ? (
                                                        <Button variant="ghost" size="sm" asChild>
                                                            <a href={`/portal/receipts/${tx.receiptId}`}>
                                                                <Download className="mr-1 h-3.5 w-3.5" /> {tx.receiptNumber}
                                                            </a>
                                                        </Button>
                                                    ) : '—'}
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

import { StatCard } from '@/components/stat-card';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/format';
import { payInstallmentOnline } from '@/lib/razorpay';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { CircleDollarSign, Wallet } from 'lucide-react';
import { useState } from 'react';

interface Installment {
    id: string;
    periodNo: number;
    dueDate: string | null;
    amountDue: number;
    lateFee: number;
    amountPaid: number;
    outstanding: number;
    status: string;
    isPayable: boolean;
}
interface Chitty {
    id: string;
    code: string;
    name: string;
    installmentAmount: number;
    durationMonths: number;
    status: string;
    ticketNumber: number | null;
    maturityDate: string | null;
}
interface Props {
    chitty: Chitty;
    installments: Installment[];
    totalOutstanding: number;
}

export default function PortalChittyShow({ chitty, installments, totalOutstanding }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'My Chitties', href: '/portal/chitties' },
        { title: chitty.code, href: `/portal/chitties/${chitty.id}` },
    ];

    const [payingId, setPayingId] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    const pay = (installmentId: string) => {
        setError(null);
        setPayingId(installmentId);
        void payInstallmentOnline(installmentId, {
            onError: (message) => {
                setError(message);
                setPayingId(null);
            },
            onSettled: () => setPayingId(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={chitty.name} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">{chitty.name}</h1>
                    <div className="text-sm text-muted-foreground">{chitty.code} · Ticket #{chitty.ticketNumber}</div>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard title="Installment" value={formatCurrency(chitty.installmentAmount)} icon={Wallet} />
                    <StatCard title="Total Outstanding" value={formatCurrency(totalOutstanding)} icon={CircleDollarSign} accent={totalOutstanding > 0 ? 'amber' : 'emerald'} />
                    <StatCard title="Maturity" value={formatDate(chitty.maturityDate)} />
                </div>

                {error && (
                    <div className="rounded-md border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                        {error}
                    </div>
                )}

                <Card>
                    <CardHeader><CardTitle>Installment Ledger</CardTitle></CardHeader>
                    <CardContent>
                        {installments.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">The schedule has not been generated yet.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="py-2 pr-4 font-medium">Period</th>
                                            <th className="py-2 pr-4 font-medium">Due Date</th>
                                            <th className="py-2 pr-4 font-medium">Amount</th>
                                            <th className="py-2 pr-4 font-medium">Late Fee</th>
                                            <th className="py-2 pr-4 font-medium">Paid</th>
                                            <th className="py-2 pr-4 font-medium">Status</th>
                                            <th className="py-2 pr-4 font-medium"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {installments.map((i) => (
                                            <tr key={i.id} className="border-b last:border-0">
                                                <td className="py-2 pr-4">#{i.periodNo}</td>
                                                <td className="py-2 pr-4">{formatDate(i.dueDate)}</td>
                                                <td className="py-2 pr-4">{formatCurrency(i.amountDue)}</td>
                                                <td className="py-2 pr-4">{i.lateFee > 0 ? formatCurrency(i.lateFee) : '—'}</td>
                                                <td className="py-2 pr-4">{formatCurrency(i.amountPaid)}</td>
                                                <td className="py-2 pr-4"><StatusBadge status={i.status} /></td>
                                                <td className="py-2 pr-4 text-right">
                                                    {i.isPayable && (
                                                        <Button size="sm" onClick={() => pay(i.id)} disabled={payingId !== null}>
                                                            {payingId === i.id ? 'Processing…' : `Pay ${formatCurrency(i.outstanding)}`}
                                                        </Button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

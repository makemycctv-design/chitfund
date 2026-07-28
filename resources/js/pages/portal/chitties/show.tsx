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

/** Google's four-colour "G" mark, rendered inline so we avoid an extra asset. */
function GooglePayMark({ className }: { className?: string }) {
    return (
        <svg viewBox="0 0 24 24" className={className} aria-hidden="true">
            <path fill="#4285F4" d="M23.04 12.26c0-.82-.07-1.6-.21-2.36H12v4.47h6.19a5.3 5.3 0 0 1-2.29 3.48v2.9h3.7c2.17-2 3.44-4.94 3.44-8.49Z" />
            <path fill="#34A853" d="M12 24c3.1 0 5.7-1.03 7.6-2.79l-3.7-2.9c-1.03.69-2.35 1.1-3.9 1.1-3 0-5.53-2.03-6.44-4.75H1.72v2.98A11.99 11.99 0 0 0 12 24Z" />
            <path fill="#FBBC05" d="M5.56 14.66a7.2 7.2 0 0 1 0-4.6V7.08H1.72a12 12 0 0 0 0 10.56l3.84-2.98Z" />
            <path fill="#EA4335" d="M12 4.75c1.69 0 3.2.58 4.4 1.72l3.28-3.28C17.7 1.2 15.1 0 12 0A11.99 11.99 0 0 0 1.72 7.08l3.84 2.98C6.47 6.78 9 4.75 12 4.75Z" />
        </svg>
    );
}

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

    // The "next installment" is the earliest payable row; it gets the Google Pay shortcut.
    const nextPayableId = installments.find((i) => i.isPayable)?.id ?? null;

    const pay = (installmentId: string, preferGooglePay = false) => {
        setError(null);
        setPayingId(installmentId);
        void payInstallmentOnline(installmentId, {
            preferGooglePay,
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
                                                <td className="py-2 pr-4">
                                                    {i.isPayable && (
                                                        <div className="flex justify-end gap-2">
                                                            {i.id === nextPayableId && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    onClick={() => pay(i.id, true)}
                                                                    disabled={payingId !== null}
                                                                >
                                                                    <GooglePayMark className="mr-1.5 h-4 w-4" />
                                                                    {payingId === i.id ? 'Processing…' : 'Google Pay'}
                                                                </Button>
                                                            )}
                                                            <Button size="sm" onClick={() => pay(i.id)} disabled={payingId !== null}>
                                                                {payingId === i.id ? 'Processing…' : `Pay ${formatCurrency(i.outstanding)}`}
                                                            </Button>
                                                        </div>
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

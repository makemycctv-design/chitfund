import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/format';
import { useTranslations } from '@/lib/i18n';
import { payInstallmentOnline } from '@/lib/razorpay';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CalendarClock, CircleDollarSign, Wallet } from 'lucide-react';
import { useState } from 'react';

/** Google's four-colour "G" mark, rendered inline. */
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

interface ChittyRow {
    id: string;
    code: string;
    name: string;
    ticketNumber: number | null;
    installmentAmount: number;
    nextAuctionDate: string | null;
    maturityDate: string | null;
    isPrized: boolean;
}

interface Summary {
    activeChittyCount: number;
    nextDueAmount: number;
    nextDueDate: string | null;
    nextDueInstallmentId: string | null;
    overdueAmount: number;
    upcomingAuctionDate: string | null;
    registrationStatus: string | null;
    kycStatus: string | null;
    kycLevel: number;
}

interface Props {
    summary: Summary;
    activeChitties: ChittyRow[];
}

function statusVariant(status: string | null): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'verified':
        case 'approved':
            return 'default';
        case 'rejected':
            return 'destructive';
        case 'pending':
        case 'submitted':
            return 'secondary';
        default:
            return 'outline';
    }
}

export default function PortalDashboard({ summary, activeChitties }: Props) {
    const { t } = useTranslations();
    const breadcrumbs: BreadcrumbItem[] = [{ title: t('portal_dashboard.title'), href: '/portal/dashboard' }];

    const [paying, setPaying] = useState(false);
    const [payError, setPayError] = useState<string | null>(null);

    const canPay = !!summary.nextDueInstallmentId && summary.nextDueAmount > 0;

    const payNext = (preferGooglePay: boolean) => {
        if (!summary.nextDueInstallmentId) return;
        setPayError(null);
        setPaying(true);
        void payInstallmentOnline(summary.nextDueInstallmentId, {
            preferGooglePay,
            onError: (message) => {
                setPayError(message);
                setPaying(false);
            },
            onSettled: () => setPaying(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('portal_dashboard.title')} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                {/* Account status banners */}
                <div className="flex flex-wrap items-center gap-3">
                    <span className="text-sm text-muted-foreground">{t('portal_dashboard.registration_status')}:</span>
                    <Badge variant={statusVariant(summary.registrationStatus)} className="capitalize">
                        {summary.registrationStatus ?? '—'}
                    </Badge>
                    <span className="ml-2 text-sm text-muted-foreground">{t('portal_dashboard.kyc_status')}:</span>
                    <Badge variant={statusVariant(summary.kycStatus)} className="capitalize">
                        {summary.kycStatus ?? '—'}
                    </Badge>
                </div>

                {/* KPIs */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title={t('portal_dashboard.next_due_amount')}
                        value={formatCurrency(summary.nextDueAmount)}
                        icon={CircleDollarSign}
                        hint={summary.nextDueDate ? formatDate(summary.nextDueDate) : undefined}
                        accent="blue"
                    />
                    <StatCard
                        title={t('portal_dashboard.overdue_amount')}
                        value={formatCurrency(summary.overdueAmount)}
                        icon={CircleDollarSign}
                        accent={summary.overdueAmount > 0 ? 'red' : 'emerald'}
                    />
                    <StatCard
                        title={t('portal_dashboard.active_chitties')}
                        value={summary.activeChittyCount}
                        icon={Wallet}
                    />
                    <StatCard
                        title={t('portal_dashboard.upcoming_auction')}
                        value={summary.upcomingAuctionDate ? formatDate(summary.upcomingAuctionDate) : '—'}
                        icon={CalendarClock}
                    />
                </div>

                {/* Next installment — pay online */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('portal_dashboard.next_installment', 'Next installment')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {canPay ? (
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div className="text-2xl font-bold tracking-tight">{formatCurrency(summary.nextDueAmount)}</div>
                                    <p className="text-sm text-muted-foreground">
                                        {summary.nextDueDate
                                            ? `${t('portal_dashboard.due', 'Due')} ${formatDate(summary.nextDueDate)}`
                                            : t('portal_dashboard.due_now', 'Due now')}
                                    </p>
                                    {payError && <p className="mt-1 text-sm text-destructive">{payError}</p>}
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Button variant="outline" onClick={() => payNext(true)} disabled={paying}>
                                        <GooglePayMark className="mr-2 h-4 w-4" />
                                        {paying ? t('portal_dashboard.processing', 'Processing…') : 'Google Pay'}
                                    </Button>
                                    <Button onClick={() => payNext(false)} disabled={paying}>
                                        {paying
                                            ? t('portal_dashboard.processing', 'Processing…')
                                            : `${t('portal_dashboard.pay_now', 'Pay now')} ${formatCurrency(summary.nextDueAmount)}`}
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    {t('portal_dashboard.all_settled', 'You have no installments due right now.')}
                                </p>
                                <Link href="/portal/payments" className="text-sm font-medium text-primary hover:underline">
                                    {t('portal_dashboard.payment_history', 'Payment history')}
                                </Link>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Active chitties */}
                <Card>
                    <CardHeader>
                        <CardTitle>{t('portal_dashboard.my_active_chitties')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {activeChitties.length === 0 ? (
                            <p className="py-8 text-center text-sm text-muted-foreground">
                                {t('portal_dashboard.no_chitties')}
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="py-2 pr-4 font-medium">#</th>
                                            <th className="py-2 pr-4 font-medium">{t('portal_dashboard.ticket')}</th>
                                            <th className="py-2 pr-4 font-medium">{t('portal_dashboard.installment')}</th>
                                            <th className="py-2 pr-4 font-medium">{t('portal_dashboard.next_auction')}</th>
                                            <th className="py-2 pr-4 font-medium">{t('portal_dashboard.maturity')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {activeChitties.map((c) => (
                                            <tr key={c.id} className="border-b last:border-0">
                                                <td className="py-3 pr-4">
                                                    <div className="font-medium">{c.name}</div>
                                                    <div className="text-xs text-muted-foreground">{c.code}</div>
                                                </td>
                                                <td className="py-3 pr-4">{c.ticketNumber ?? '—'}</td>
                                                <td className="py-3 pr-4 font-medium">{formatCurrency(c.installmentAmount)}</td>
                                                <td className="py-3 pr-4">{formatDate(c.nextAuctionDate)}</td>
                                                <td className="py-3 pr-4">{formatDate(c.maturityDate)}</td>
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

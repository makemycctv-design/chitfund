import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/format';
import { useTranslations } from '@/lib/i18n';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { CalendarClock, CircleDollarSign, Wallet } from 'lucide-react';

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

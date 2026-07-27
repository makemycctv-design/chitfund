import { StatusBadge } from '@/components/status-badge';
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency } from '@/lib/format';
import { useTranslations } from '@/lib/i18n';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface ChittyRow {
    id: string | null;
    code: string | null;
    name: string | null;
    ticketNumber: number | null;
    installmentAmount: number;
    durationMonths: number | null;
    status: string;
    chittyStatus: string | null;
    isPrized: boolean;
}

export default function PortalChitties({ chitties }: { chitties: ChittyRow[] }) {
    const { t } = useTranslations();
    const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.my_chitties'), href: '/portal/chitties' }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('nav.my_chitties')} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">{t('nav.my_chitties')}</h1>
                {chitties.length === 0 ? (
                    <Card><CardContent className="py-10 text-center text-muted-foreground">{t('portal_dashboard.no_chitties')}</CardContent></Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {chitties.map((c) => (
                            <Link key={c.id} href={c.id ? `/portal/chitties/${c.id}` : '#'}>
                                <Card className="transition hover:border-primary">
                                    <CardContent className="pt-6">
                                        <div className="flex items-center justify-between">
                                            <div className="font-medium">{c.name}</div>
                                            <StatusBadge status={c.chittyStatus} />
                                        </div>
                                        <div className="text-xs text-muted-foreground">{c.code} · Ticket #{c.ticketNumber}</div>
                                        <div className="mt-3 text-lg font-semibold">{formatCurrency(c.installmentAmount)}<span className="text-xs font-normal text-muted-foreground">/installment</span></div>
                                        <div className="text-xs text-muted-foreground">{c.durationMonths} months</div>
                                        {c.isPrized && <div className="mt-2 text-xs text-amber-600">Prize won</div>}
                                    </CardContent>
                                </Card>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import { useTranslations } from '@/lib/i18n';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface AuctionRow {
    id: string;
    chitty: string | null;
    chittyName: string | null;
    periodNo: number;
    status: string;
    scheduledAt: string | null;
    endsAt: string | null;
    eligible: boolean;
}

export default function PortalAuctions({ auctions }: { auctions: AuctionRow[] }) {
    const { t } = useTranslations();
    const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.auctions'), href: '/portal/auctions' }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('nav.auctions')} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">{t('nav.auctions')}</h1>
                {auctions.length === 0 ? (
                    <Card><CardContent className="py-10 text-center text-muted-foreground">No auctions for your chitties yet.</CardContent></Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {auctions.map((a) => (
                            <Card key={a.id}>
                                <CardContent className="pt-6">
                                    <div className="flex items-center justify-between">
                                        <div className="font-medium">{a.chittyName}</div>
                                        <StatusBadge status={a.status} />
                                    </div>
                                    <div className="text-xs text-muted-foreground">{a.chitty} · Period #{a.periodNo}</div>
                                    <div className="mt-2 text-xs text-muted-foreground">{formatDateTime(a.scheduledAt)}</div>
                                    <div className="mt-3">
                                        {['live', 'scheduled', 'paused'].includes(a.status) ? (
                                            <Button size="sm" asChild disabled={!a.eligible}>
                                                <Link href={`/portal/auctions/${a.id}`}>{a.eligible ? 'Enter Room' : 'View'}</Link>
                                            </Button>
                                        ) : (
                                            <Button size="sm" variant="outline" asChild>
                                                <Link href={`/portal/auctions/${a.id}`}>View Result</Link>
                                            </Button>
                                        )}
                                    </div>
                                    {!a.eligible && <p className="mt-2 text-xs text-destructive">Not eligible to bid</p>}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

import { StatCard } from '@/components/stat-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslations } from '@/lib/i18n';
import { formatCurrencyCompact, formatDateTime } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { Banknote, CalendarClock, FileWarning, ShieldCheck, UserCheck, Users, Wallet } from 'lucide-react';

interface Stats {
    activeChitties: number;
    totalChitties: number;
    totalSubscribers: number;
    totalBranches: number;
    pendingRegistrations: number;
    pendingKyc: number;
    upcomingAuctions: number;
    portfolioValue: number;
}

interface StatusDatum {
    status: string;
    label: string;
    count: number;
}

interface ActivityItem {
    id: number;
    description: string;
    subject_type: string;
    causer: string | null;
    created_at: string | null;
}

interface Props {
    stats: Stats;
    chittiesByStatus: StatusDatum[];
    recentActivity: ActivityItem[];
}

export default function AdminDashboard({ stats, chittiesByStatus, recentActivity }: Props) {
    const { t } = useTranslations();

    const breadcrumbs: BreadcrumbItem[] = [{ title: t('admin_dashboard.title'), href: '/admin/dashboard' }];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('admin_dashboard.title')} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                {/* Primary KPIs */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title={t('admin_dashboard.active_chitties')}
                        value={stats.activeChitties}
                        hint={`${stats.totalChitties} total`}
                        icon={Wallet}
                        accent="blue"
                    />
                    <StatCard
                        title={t('admin_dashboard.total_subscribers')}
                        value={stats.totalSubscribers.toLocaleString('en-IN')}
                        icon={Users}
                    />
                    <StatCard title={t('admin_dashboard.branches')} value={stats.totalBranches} icon={ShieldCheck} />
                    <StatCard
                        title={t('admin_dashboard.portfolio_value')}
                        value={formatCurrencyCompact(stats.portfolioValue)}
                        icon={Banknote}
                        accent="emerald"
                    />
                </div>

                {/* Operational KPIs */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        title={t('admin_dashboard.pending_registrations')}
                        value={stats.pendingRegistrations}
                        icon={UserCheck}
                        accent={stats.pendingRegistrations > 0 ? 'amber' : 'default'}
                    />
                    <StatCard
                        title={t('admin_dashboard.pending_kyc')}
                        value={stats.pendingKyc}
                        icon={FileWarning}
                        accent={stats.pendingKyc > 0 ? 'amber' : 'default'}
                    />
                    <StatCard
                        title={t('admin_dashboard.upcoming_auctions')}
                        value={stats.upcomingAuctions}
                        icon={CalendarClock}
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-5">
                    {/* Chitties by status chart */}
                    <Card className="lg:col-span-3">
                        <CardHeader>
                            <CardTitle>{t('admin_dashboard.chitties_by_status')}</CardTitle>
                        </CardHeader>
                        <CardContent className="h-72">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={chittiesByStatus} margin={{ top: 8, right: 8, bottom: 8, left: -16 }}>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" vertical={false} />
                                    <XAxis
                                        dataKey="label"
                                        tick={{ fontSize: 11 }}
                                        interval={0}
                                        angle={-20}
                                        textAnchor="end"
                                        height={60}
                                    />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <Tooltip
                                        cursor={{ fill: 'hsl(var(--muted))', opacity: 0.3 }}
                                        contentStyle={{
                                            borderRadius: 8,
                                            border: '1px solid hsl(var(--border))',
                                            background: 'hsl(var(--background))',
                                            fontSize: 12,
                                        }}
                                    />
                                    <Bar dataKey="count" radius={[4, 4, 0, 0]} fill="#2563eb" />
                                </BarChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    {/* Recent activity */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>{t('admin_dashboard.recent_activity')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recentActivity.length === 0 ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">
                                    {t('admin_dashboard.no_activity')}
                                </p>
                            ) : (
                                <ul className="space-y-3">
                                    {recentActivity.map((item) => (
                                        <li key={item.id} className="flex flex-col border-b border-border/50 pb-2 last:border-0">
                                            <span className="text-sm capitalize">{item.description}</span>
                                            <span className="text-xs text-muted-foreground">
                                                {[item.subject_type, item.causer].filter(Boolean).join(' • ')}
                                                {item.created_at ? ` • ${formatDateTime(item.created_at)}` : ''}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

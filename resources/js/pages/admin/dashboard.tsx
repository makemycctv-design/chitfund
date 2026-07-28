import { StatCard } from '@/components/stat-card';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { usePermissions } from '@/hooks/use-permissions';
import { useTranslations } from '@/lib/i18n';
import { formatCurrency, formatCurrencyCompact, formatDateTime } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import {
    AlertTriangle,
    Banknote,
    Building2,
    CalendarClock,
    FileBarChart,
    FileWarning,
    Gavel,
    Layers,
    LifeBuoy,
    PlusCircle,
    ShieldCheck,
    UserCheck,
    Users,
    Wallet,
    type LucideIcon,
} from 'lucide-react';

interface Stats {
    activeChitties: number;
    totalChitties: number;
    totalSubscribers: number;
    totalBranches: number;
    pendingRegistrations: number;
    pendingKyc: number;
    liveAuctions: number;
    upcomingAuctions: number;
    overdueCount: number;
    overdueAmount: number;
    openTickets: number;
    portfolioValue: number;
}

interface StatusDatum {
    status: string;
    label: string;
    count: number;
}

interface BranchRow {
    id: number;
    name: string;
    code: string;
    activeChitties: number;
    subscribers: number;
    pendingApprovals: number;
    overdueAmount: number;
}

interface ActivityItem {
    id: number;
    description: string;
    subject_type: string;
    causer: string | null;
    created_at: string | null;
}

interface RoleInfo {
    name: string | null;
    label: string;
    scopeLabel: string;
    isBranchScoped: boolean;
}

interface Props {
    role: RoleInfo;
    stats: Stats;
    chittiesByStatus: StatusDatum[];
    branchBreakdown: BranchRow[];
    recentActivity: ActivityItem[];
}

/** A StatCard that becomes a link when `href` is provided. */
function LinkableStat({ href, ...props }: { href?: string } & React.ComponentProps<typeof StatCard>) {
    const card = <StatCard {...props} />;
    return href ? (
        <Link href={href} className="block transition-opacity hover:opacity-90">
            {card}
        </Link>
    ) : (
        card
    );
}

interface QuickAction {
    label: string;
    href: string;
    icon: LucideIcon;
    show: boolean;
}

export default function AdminDashboard({ role, stats, chittiesByStatus, branchBreakdown, recentActivity }: Props) {
    const { t } = useTranslations();
    const { can, canAny } = usePermissions();

    const breadcrumbs: BreadcrumbItem[] = [{ title: t('admin_dashboard.title'), href: '/admin/dashboard' }];

    const quickActions: QuickAction[] = [
        { label: t('nav.create_chitty', 'New chitty'), href: '/admin/chitties/create', icon: PlusCircle, show: can('chitties.create') },
        { label: t('admin_dashboard.approve_customers', 'Approve customers'), href: '/admin/customers', icon: UserCheck, show: can('customers.approve-registration') },
        { label: t('admin_dashboard.manage_auctions', 'Manage auctions'), href: '/admin/auctions', icon: Gavel, show: canAny(['auctions.start', 'auctions.close', 'chitties.view']) },
        { label: t('admin_dashboard.collections', 'Record collection'), href: '/admin/collections', icon: Banknote, show: can('collections.record') },
        { label: t('admin_dashboard.reports', 'Reports'), href: '/admin/reports/collections', icon: FileBarChart, show: can('reports.view') },
        { label: t('admin_dashboard.support', 'Support'), href: '/admin/support', icon: LifeBuoy, show: can('customers.view') },
        { label: t('nav.schemes', 'Schemes'), href: '/admin/schemes', icon: Layers, show: can('chitty-schemes.manage') },
    ].filter((a) => a.show);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('admin_dashboard.title')} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                {/* Role + scope header */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold tracking-tight">{role.label}</h1>
                        <p className="text-sm text-muted-foreground">
                            {t('admin_dashboard.viewing', 'Viewing')}: {role.scopeLabel}
                        </p>
                    </div>
                    <Badge variant="outline" className="flex items-center gap-1.5">
                        <Building2 className="h-3.5 w-3.5" />
                        {role.scopeLabel}
                    </Badge>
                </div>

                {/* Quick actions */}
                {quickActions.length > 0 && (
                    <div className="flex flex-wrap gap-2">
                        {quickActions.map((a) => (
                            <Link
                                key={a.href}
                                href={a.href}
                                className="inline-flex items-center gap-2 rounded-md border border-border bg-card px-3 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground"
                            >
                                <a.icon className="h-4 w-4" />
                                {a.label}
                            </Link>
                        ))}
                    </div>
                )}

                {/* Primary KPIs */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <LinkableStat
                        href={can('chitties.view') ? '/admin/chitties' : undefined}
                        title={t('admin_dashboard.active_chitties')}
                        value={stats.activeChitties}
                        hint={`${stats.totalChitties} total`}
                        icon={Wallet}
                        accent="blue"
                    />
                    <LinkableStat
                        href={can('customers.view') ? '/admin/customers' : undefined}
                        title={t('admin_dashboard.total_subscribers')}
                        value={stats.totalSubscribers.toLocaleString('en-IN')}
                        icon={Users}
                    />
                    {!role.isBranchScoped && (
                        <StatCard title={t('admin_dashboard.branches')} value={stats.totalBranches} icon={ShieldCheck} />
                    )}
                    <StatCard
                        title={t('admin_dashboard.portfolio_value')}
                        value={formatCurrencyCompact(stats.portfolioValue)}
                        icon={Banknote}
                        accent="emerald"
                    />
                </div>

                {/* Action items — each card is permission-gated and links to its queue */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {can('customers.approve-registration') && (
                        <LinkableStat
                            href="/admin/customers"
                            title={t('admin_dashboard.pending_registrations')}
                            value={stats.pendingRegistrations}
                            icon={UserCheck}
                            accent={stats.pendingRegistrations > 0 ? 'amber' : 'default'}
                        />
                    )}
                    {can('kyc.verify') && (
                        <LinkableStat
                            href="/admin/customers"
                            title={t('admin_dashboard.pending_kyc')}
                            value={stats.pendingKyc}
                            icon={FileWarning}
                            accent={stats.pendingKyc > 0 ? 'amber' : 'default'}
                        />
                    )}
                    {can('chitties.view') && (
                        <LinkableStat
                            href="/admin/auctions"
                            title={t('admin_dashboard.auctions', 'Auctions (live / upcoming)')}
                            value={`${stats.liveAuctions} / ${stats.upcomingAuctions}`}
                            icon={CalendarClock}
                            accent={stats.liveAuctions > 0 ? 'blue' : 'default'}
                        />
                    )}
                    {can('reports.view') && (
                        <LinkableStat
                            href="/admin/reports/overdue"
                            title={t('admin_dashboard.overdue', 'Overdue')}
                            value={formatCurrencyCompact(stats.overdueAmount)}
                            hint={`${stats.overdueCount} installment(s)`}
                            icon={AlertTriangle}
                            accent={stats.overdueAmount > 0 ? 'red' : 'emerald'}
                        />
                    )}
                    {can('customers.view') && (
                        <LinkableStat
                            href="/admin/support"
                            title={t('admin_dashboard.open_tickets', 'Open support tickets')}
                            value={stats.openTickets}
                            icon={LifeBuoy}
                            accent={stats.openTickets > 0 ? 'amber' : 'default'}
                        />
                    )}
                </div>

                {/* Per-branch breakdown for company-wide viewers */}
                {branchBreakdown.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('admin_dashboard.branch_breakdown', 'Branch breakdown')}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="py-2 pr-4 font-medium">{t('admin_dashboard.branch', 'Branch')}</th>
                                            <th className="py-2 pr-4 font-medium">{t('admin_dashboard.active_chitties', 'Active chitties')}</th>
                                            <th className="py-2 pr-4 font-medium">{t('admin_dashboard.total_subscribers', 'Subscribers')}</th>
                                            <th className="py-2 pr-4 font-medium">{t('admin_dashboard.pending', 'Pending')}</th>
                                            <th className="py-2 pr-4 text-right font-medium">{t('admin_dashboard.overdue', 'Overdue')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {branchBreakdown.map((b) => (
                                            <tr key={b.id} className="border-b last:border-0">
                                                <td className="py-2 pr-4">
                                                    <div className="font-medium">{b.name}</div>
                                                    <div className="text-xs text-muted-foreground">{b.code}</div>
                                                </td>
                                                <td className="py-2 pr-4">{b.activeChitties}</td>
                                                <td className="py-2 pr-4">{b.subscribers.toLocaleString('en-IN')}</td>
                                                <td className="py-2 pr-4">
                                                    {b.pendingApprovals > 0 ? (
                                                        <span className="font-medium text-amber-600 dark:text-amber-500">
                                                            {b.pendingApprovals}
                                                        </span>
                                                    ) : (
                                                        0
                                                    )}
                                                </td>
                                                <td className="py-2 pr-4 text-right">
                                                    <span className={b.overdueAmount > 0 ? 'font-medium text-red-600 dark:text-red-500' : ''}>
                                                        {formatCurrency(b.overdueAmount)}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

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

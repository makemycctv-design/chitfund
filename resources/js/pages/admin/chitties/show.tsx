import { StatCard } from '@/components/stat-card';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { SelectOption } from '@/types/pagination';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Banknote, CalendarClock, Pencil, Users } from 'lucide-react';

interface Chitty {
    id: string;
    code: string;
    name: string;
    branch: string | null;
    scheme: string | null;
    chitValue: number;
    installmentAmount: number;
    durationMonths: number;
    totalSubscribers: number;
    foremanCommissionPercent: number;
    gracePeriodDays: number;
    startDate: string | null;
    maturityDate: string | null;
    status: string;
    statusLabel: string;
    hasSchedule: boolean;
    availableSlots: number;
    notes: string | null;
}
interface Membership {
    id: string;
    ticketNumber: number | null;
    customerName: string | null;
    customerEmail: string | null;
    status: string;
    isPrized: boolean;
}
interface Ledger {
    collected: number;
    due: number;
    outstanding: number;
    overdueCount: number;
}
interface Props {
    chitty: Chitty;
    memberships: Membership[];
    ledger: Ledger;
    allowedTransitions: SelectOption[];
    eligibleCustomers: SelectOption[];
}

export default function ChittyShow({ chitty, memberships, ledger, allowedTransitions, eligibleCustomers }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Chitties', href: '/admin/chitties' },
        { title: chitty.code, href: `/admin/chitties/${chitty.id}` },
    ];

    const enrollForm = useForm({ customer_id: '' });

    const transition = (status: string) => {
        router.post(`/admin/chitties/${chitty.id}/transition`, { status }, { preserveScroll: true });
    };

    const generateSchedule = () => {
        router.post(`/admin/chitties/${chitty.id}/generate-schedule`, {}, { preserveScroll: true });
    };

    const enroll = (e: React.FormEvent) => {
        e.preventDefault();
        enrollForm.post(`/admin/chitties/${chitty.id}/enroll`, {
            preserveScroll: true,
            onSuccess: () => enrollForm.reset('customer_id'),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={chitty.code} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">{chitty.name}</h1>
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            {chitty.code} · {chitty.branch} <StatusBadge status={chitty.status} label={chitty.statusLabel} />
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={`/admin/chitties/${chitty.id}/edit`}>
                                <Pencil className="mr-1 h-4 w-4" /> Edit
                            </Link>
                        </Button>
                        {allowedTransitions.map((t) => (
                            <Button key={t.value} variant="secondary" onClick={() => transition(String(t.value))}>
                                → {t.label}
                            </Button>
                        ))}
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard title="Collected" value={formatCurrency(ledger.collected)} icon={Banknote} accent="emerald" />
                    <StatCard title="Outstanding" value={formatCurrency(ledger.outstanding)} icon={Banknote} accent={ledger.outstanding > 0 ? 'amber' : 'default'} />
                    <StatCard title="Overdue Installments" value={ledger.overdueCount} icon={CalendarClock} accent={ledger.overdueCount > 0 ? 'red' : 'default'} />
                    <StatCard title="Members" value={`${memberships.length}/${chitty.totalSubscribers}`} icon={Users} />
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Terms */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Terms</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <Row label="Chit Value" value={formatCurrency(chitty.chitValue)} />
                            <Row label="Installment" value={formatCurrency(chitty.installmentAmount)} />
                            <Row label="Duration" value={`${chitty.durationMonths} months`} />
                            <Row label="Foreman Commission" value={`${chitty.foremanCommissionPercent}%`} />
                            <Row label="Grace Period" value={`${chitty.gracePeriodDays} days`} />
                            <Row label="Start" value={formatDate(chitty.startDate)} />
                            <Row label="Maturity" value={formatDate(chitty.maturityDate)} />
                        </CardContent>
                    </Card>

                    {/* Schedule + enroll */}
                    <Card className="lg:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Subscribers</CardTitle>
                            {!chitty.hasSchedule ? (
                                <Button size="sm" onClick={generateSchedule}>
                                    Generate Schedule
                                </Button>
                            ) : (
                                <span className="text-xs text-emerald-600">Schedule generated</span>
                            )}
                        </CardHeader>
                        <CardContent>
                            {/* Enroll */}
                            <form onSubmit={enroll} className="mb-4 flex flex-wrap gap-2">
                                <select
                                    value={enrollForm.data.customer_id}
                                    onChange={(e) => enrollForm.setData('customer_id', e.target.value)}
                                    className="h-9 min-w-64 flex-1 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="">Select a KYC-verified customer…</option>
                                    {eligibleCustomers.map((c) => (
                                        <option key={c.value} value={c.value}>
                                            {c.label}
                                        </option>
                                    ))}
                                </select>
                                <Button type="submit" disabled={!enrollForm.data.customer_id || chitty.availableSlots <= 0}>
                                    Enroll ({chitty.availableSlots} left)
                                </Button>
                            </form>
                            {enrollForm.errors.customer_id && (
                                <p className="mb-2 text-xs text-destructive">{enrollForm.errors.customer_id}</p>
                            )}

                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-muted-foreground">
                                            <th className="py-2 pr-4 font-medium">Ticket</th>
                                            <th className="py-2 pr-4 font-medium">Customer</th>
                                            <th className="py-2 pr-4 font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {memberships.length === 0 ? (
                                            <tr>
                                                <td colSpan={3} className="py-6 text-center text-muted-foreground">
                                                    No subscribers enrolled yet.
                                                </td>
                                            </tr>
                                        ) : (
                                            memberships.map((m) => (
                                                <tr key={m.id} className="border-b last:border-0">
                                                    <td className="py-2 pr-4">#{m.ticketNumber}</td>
                                                    <td className="py-2 pr-4">
                                                        <div>{m.customerName}</div>
                                                        <div className="text-xs text-muted-foreground">{m.customerEmail}</div>
                                                    </td>
                                                    <td className="py-2 pr-4">
                                                        <StatusBadge status={m.status} />
                                                        {m.isPrized && <span className="ml-1 text-xs text-amber-600">Prized</span>}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between border-b border-border/40 pb-1 last:border-0">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-medium">{value}</span>
        </div>
    );
}

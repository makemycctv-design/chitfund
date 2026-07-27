import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatCurrency } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { Banknote, Download, Hash } from 'lucide-react';

interface Props {
    range: { from: string; to: string };
    summary: { total: number; count: number };
    daily: { day: string; count: number; total: number }[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Reports', href: '#' }, { title: 'Collections', href: '/admin/reports/collections' }];

export default function CollectionsReport({ range, summary, daily }: Props) {
    const [from, setFrom] = useState(range.from);
    const [to, setTo] = useState(range.to);

    const apply = () => router.get('/admin/reports/collections', { from, to }, { preserveState: true });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Collections Report" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-end justify-between gap-2">
                    <h1 className="text-xl font-semibold">Collections Report</h1>
                    <div className="flex flex-wrap items-end gap-2">
                        <div className="grid gap-1">
                            <label className="text-xs text-muted-foreground">From</label>
                            <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
                        </div>
                        <div className="grid gap-1">
                            <label className="text-xs text-muted-foreground">To</label>
                            <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} />
                        </div>
                        <Button variant="secondary" onClick={apply}>Apply</Button>
                        <Button variant="outline" asChild>
                            <a href={`/admin/reports/collections/export?from=${from}&to=${to}`}>
                                <Download className="mr-1 h-4 w-4" /> Excel
                            </a>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <StatCard title="Total Collected" value={formatCurrency(summary.total)} icon={Banknote} accent="emerald" />
                    <StatCard title="Transactions" value={summary.count} icon={Hash} />
                </div>

                <Card>
                    <CardHeader><CardTitle>Daily Collections</CardTitle></CardHeader>
                    <CardContent className="h-72">
                        {daily.length === 0 ? (
                            <p className="flex h-full items-center justify-center text-sm text-muted-foreground">No collections in this period.</p>
                        ) : (
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={daily} margin={{ top: 8, right: 8, bottom: 8, left: -8 }}>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" vertical={false} />
                                    <XAxis dataKey="day" tick={{ fontSize: 11 }} />
                                    <YAxis tick={{ fontSize: 11 }} />
                                    <Tooltip
                                        formatter={(value) => formatCurrency(Number(value))}
                                        contentStyle={{ borderRadius: 8, border: '1px solid hsl(var(--border))', background: 'hsl(var(--background))', fontSize: 12 }}
                                    />
                                    <Bar dataKey="total" radius={[4, 4, 0, 0]} fill="#059669" />
                                </BarChart>
                            </ResponsiveContainer>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

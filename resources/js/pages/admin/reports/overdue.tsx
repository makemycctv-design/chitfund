import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency, formatDate } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { AlertTriangle, Download, Hash } from 'lucide-react';

interface Row {
    customer: string | null;
    phone: string | null;
    chitty: string | null;
    periodNo: number;
    dueDate: string | null;
    outstanding: number;
}
interface Props {
    summary: { count: number; totalOutstanding: number };
    rows: Row[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Reports', href: '#' }, { title: 'Overdue', href: '/admin/reports/overdue' }];

export default function OverdueReport({ summary, rows }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Overdue Report" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Overdue Installments</h1>
                    <Button variant="outline" asChild>
                        <a href="/admin/reports/overdue/export">
                            <Download className="mr-1 h-4 w-4" /> Excel
                        </a>
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <StatCard title="Overdue Installments" value={summary.count} icon={Hash} accent={summary.count > 0 ? 'red' : 'default'} />
                    <StatCard title="Total Outstanding" value={formatCurrency(summary.totalOutstanding)} icon={AlertTriangle} accent={summary.totalOutstanding > 0 ? 'red' : 'emerald'} />
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">Customer</th>
                                        <th className="py-2 pr-4 font-medium">Phone</th>
                                        <th className="py-2 pr-4 font-medium">Chitty</th>
                                        <th className="py-2 pr-4 font-medium">Period</th>
                                        <th className="py-2 pr-4 font-medium">Due Date</th>
                                        <th className="py-2 pr-4 font-medium">Outstanding</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.length === 0 ? (
                                        <tr><td colSpan={6} className="py-8 text-center text-muted-foreground">No overdue installments. 🎉</td></tr>
                                    ) : (
                                        rows.map((r, i) => (
                                            <tr key={i} className="border-b last:border-0">
                                                <td className="py-2 pr-4">{r.customer}</td>
                                                <td className="py-2 pr-4">{r.phone}</td>
                                                <td className="py-2 pr-4">{r.chitty}</td>
                                                <td className="py-2 pr-4">#{r.periodNo}</td>
                                                <td className="py-2 pr-4">{formatDate(r.dueDate)}</td>
                                                <td className="py-2 pr-4 font-medium text-red-600">{formatCurrency(r.outstanding)}</td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

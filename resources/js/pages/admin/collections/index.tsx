import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatCurrency, formatDate } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { Paginated, SelectOption } from '@/types/pagination';
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

interface Installment {
    id: string;
    customerName: string | null;
    customerPhone: string | null;
    chitty: string | null;
    periodNo: number;
    dueDate: string | null;
    amountDue: number;
    lateFee: number;
    outstanding: number;
    status: string;
    isOverdue: boolean;
}
interface Props {
    installments: Paginated<Installment>;
    filters: { search?: string; overdue_only?: boolean };
    methods: SelectOption[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Collections', href: '/admin/collections' }];

export default function CollectionsIndex({ installments, filters, methods }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [selected, setSelected] = useState<Installment | null>(null);

    const form = useForm({ installment_id: '', method: methods[0]?.value ?? 'cash', amount: '', note: '' });

    const openRecord = (i: Installment) => {
        setSelected(i);
        form.setData({ installment_id: i.id, method: methods[0]?.value ?? 'cash', amount: String(i.outstanding), note: '' });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/admin/collections', {
            preserveScroll: true,
            onSuccess: () => setSelected(null),
        });
    };

    const apply = (patch: Record<string, string | boolean>) =>
        router.get('/admin/collections', { ...filters, ...patch }, { preserveState: true, replace: true });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Collections" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Collections</h1>
                <Card>
                    <CardContent className="pt-6">
                        <form className="mb-4 flex flex-wrap items-center gap-2" onSubmit={(e) => { e.preventDefault(); apply({ search }); }}>
                            <Input placeholder="Search customer…" value={search} onChange={(e) => setSearch(e.target.value)} className="max-w-xs" />
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={!!filters.overdue_only}
                                    onChange={(e) => apply({ overdue_only: e.target.checked ? '1' : '' })}
                                />
                                Overdue only
                            </label>
                            <Button type="submit" variant="secondary">Search</Button>
                        </form>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">Customer</th>
                                        <th className="py-2 pr-4 font-medium">Chitty</th>
                                        <th className="py-2 pr-4 font-medium">Period</th>
                                        <th className="py-2 pr-4 font-medium">Due</th>
                                        <th className="py-2 pr-4 font-medium">Outstanding</th>
                                        <th className="py-2 pr-4 font-medium">Status</th>
                                        <th className="py-2 pr-4 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {installments.data.length === 0 ? (
                                        <tr><td colSpan={7} className="py-8 text-center text-muted-foreground">Nothing outstanding. 🎉</td></tr>
                                    ) : (
                                        installments.data.map((i) => (
                                            <tr key={i.id} className="border-b last:border-0">
                                                <td className="py-2 pr-4">
                                                    <div>{i.customerName}</div>
                                                    <div className="text-xs text-muted-foreground">{i.customerPhone}</div>
                                                </td>
                                                <td className="py-2 pr-4">{i.chitty}</td>
                                                <td className="py-2 pr-4">#{i.periodNo}</td>
                                                <td className="py-2 pr-4">{formatDate(i.dueDate)}</td>
                                                <td className="py-2 pr-4 font-medium">{formatCurrency(i.outstanding)}</td>
                                                <td className="py-2 pr-4"><StatusBadge status={i.isOverdue ? 'overdue' : i.status} /></td>
                                                <td className="py-2 pr-4 text-right">
                                                    <Button size="sm" onClick={() => openRecord(i)}>Record</Button>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={installments.links} from={installments.from} to={installments.to} total={installments.total} />
                    </CardContent>
                </Card>
            </div>

            <Dialog open={!!selected} onOpenChange={(o) => !o && setSelected(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Record Payment</DialogTitle>
                        <DialogDescription>
                            {selected?.customerName} · {selected?.chitty} · Period #{selected?.periodNo}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submit} className="grid gap-3">
                        <div className="grid gap-1.5">
                            <label className="text-sm">Method</label>
                            <select
                                value={form.data.method}
                                onChange={(e) => form.setData('method', e.target.value)}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                {methods.map((m) => (
                                    <option key={m.value} value={m.value}>{m.label}</option>
                                ))}
                            </select>
                        </div>
                        <div className="grid gap-1.5">
                            <label className="text-sm">Amount (₹)</label>
                            <Input type="number" step="0.01" value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} />
                            {form.errors.amount && <p className="text-xs text-destructive">{form.errors.amount}</p>}
                        </div>
                        <div className="grid gap-1.5">
                            <label className="text-sm">Note (optional)</label>
                            <Input value={form.data.note} onChange={(e) => form.setData('note', e.target.value)} />
                        </div>
                        <DialogFooter>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Recording…' : 'Record & Issue Receipt'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

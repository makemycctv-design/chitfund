import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatCurrency } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { Paginated, SelectOption } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

interface ChittyRow {
    id: string;
    code: string;
    name: string;
    branch: string | null;
    chitValue: number;
    installmentAmount: number;
    durationMonths: number;
    subscribers: number;
    totalSubscribers: number;
    status: string;
    statusLabel: string;
}

interface Props {
    chitties: Paginated<ChittyRow>;
    branches: SelectOption[];
    statuses: SelectOption[];
    filters: { search?: string; status?: string; branch_id?: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Chitties', href: '/admin/chitties' }];

export default function ChittiesIndex({ chitties, branches, statuses, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilter = (patch: Record<string, string>) => {
        router.get('/admin/chitties', { ...filters, ...patch }, { preserveState: true, replace: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Chitties" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Chitties</h1>
                    <Button asChild>
                        <Link href="/admin/chitties/create">
                            <Plus className="mr-1 h-4 w-4" /> New Chitty
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <form
                            className="mb-4 flex flex-wrap gap-2"
                            onSubmit={(e) => {
                                e.preventDefault();
                                applyFilter({ search });
                            }}
                        >
                            <Input
                                placeholder="Search code or name…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="max-w-xs"
                            />
                            <select
                                value={filters.status ?? ''}
                                onChange={(e) => applyFilter({ status: e.target.value })}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">All statuses</option>
                                {statuses.map((s) => (
                                    <option key={s.value} value={s.value}>
                                        {s.label}
                                    </option>
                                ))}
                            </select>
                            <select
                                value={filters.branch_id ?? ''}
                                onChange={(e) => applyFilter({ branch_id: e.target.value })}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">All branches</option>
                                {branches.map((b) => (
                                    <option key={b.value} value={b.value}>
                                        {b.label}
                                    </option>
                                ))}
                            </select>
                            <Button type="submit" variant="secondary">
                                Search
                            </Button>
                        </form>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">Chitty</th>
                                        <th className="py-2 pr-4 font-medium">Branch</th>
                                        <th className="py-2 pr-4 font-medium">Value</th>
                                        <th className="py-2 pr-4 font-medium">Installment</th>
                                        <th className="py-2 pr-4 font-medium">Members</th>
                                        <th className="py-2 pr-4 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {chitties.data.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="py-8 text-center text-muted-foreground">
                                                No chitties found.
                                            </td>
                                        </tr>
                                    ) : (
                                        chitties.data.map((c) => (
                                            <tr key={c.id} className="border-b last:border-0 hover:bg-muted/40">
                                                <td className="py-3 pr-4">
                                                    <Link href={`/admin/chitties/${c.id}`} className="font-medium hover:underline">
                                                        {c.name}
                                                    </Link>
                                                    <div className="text-xs text-muted-foreground">{c.code}</div>
                                                </td>
                                                <td className="py-3 pr-4">{c.branch ?? '—'}</td>
                                                <td className="py-3 pr-4">{formatCurrency(c.chitValue)}</td>
                                                <td className="py-3 pr-4">{formatCurrency(c.installmentAmount)}</td>
                                                <td className="py-3 pr-4">
                                                    {c.subscribers}/{c.totalSubscribers}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    <StatusBadge status={c.status} label={c.statusLabel} />
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <Pagination links={chitties.links} from={chitties.from} to={chitties.to} total={chitties.total} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

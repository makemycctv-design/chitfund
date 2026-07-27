import { Pagination } from '@/components/pagination';
import { Card, CardContent } from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/pagination';
import { Head, router } from '@inertiajs/react';

interface Log {
    id: number; log: string | null; description: string; subjectType: string;
    causer: string | null; properties: Record<string, unknown>; at: string | null;
}
interface Props { logs: Paginated<Log>; logNames: string[]; filters: { log?: string } }

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Audit Logs', href: '/admin/audit-logs' }];

export default function AuditIndex({ logs, logNames, filters }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit Logs" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Audit Logs</h1>
                <Card>
                    <CardContent className="pt-6">
                        <div className="mb-4">
                            <select value={filters.log ?? ''} onChange={(e) => router.get('/admin/audit-logs', { log: e.target.value }, { preserveState: true, replace: true })} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                <option value="">All logs</option>
                                {logNames.map((n) => <option key={n} value={n}>{n}</option>)}
                            </select>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">When</th>
                                        <th className="py-2 pr-4 font-medium">Log</th>
                                        <th className="py-2 pr-4 font-medium">Description</th>
                                        <th className="py-2 pr-4 font-medium">Subject</th>
                                        <th className="py-2 pr-4 font-medium">By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {logs.data.length === 0 ? (
                                        <tr><td colSpan={5} className="py-8 text-center text-muted-foreground">No activity.</td></tr>
                                    ) : logs.data.map((l) => (
                                        <tr key={l.id} className="border-b last:border-0">
                                            <td className="py-2 pr-4 text-xs">{formatDateTime(l.at)}</td>
                                            <td className="py-2 pr-4"><span className="rounded bg-muted px-1.5 py-0.5 text-xs">{l.log ?? 'default'}</span></td>
                                            <td className="py-2 pr-4 capitalize">{l.description}</td>
                                            <td className="py-2 pr-4">{l.subjectType || '—'}</td>
                                            <td className="py-2 pr-4">{l.causer ?? 'system'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={logs.links} from={logs.from} to={logs.to} total={logs.total} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

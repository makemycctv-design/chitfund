import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Card, CardContent } from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/pagination';
import { Head, router } from '@inertiajs/react';

interface Log {
    id: string; user: string | null; eventKey: string; channel: string;
    status: string; providerMessageId: string | null; error: string | null; sentAt: string | null;
}
interface Props { logs: Paginated<Log>; filters: { channel?: string; status?: string } }

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notification Logs', href: '/admin/notification-logs' }];

export default function NotificationLogs({ logs, filters }: Props) {
    const apply = (patch: Record<string, string>) => router.get('/admin/notification-logs', { ...filters, ...patch }, { preserveState: true, replace: true });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notification Logs" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Notification Delivery Logs</h1>
                <Card>
                    <CardContent className="pt-6">
                        <div className="mb-4 flex gap-2">
                            <select value={filters.channel ?? ''} onChange={(e) => apply({ channel: e.target.value })} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                <option value="">All channels</option>
                                <option value="database">In-app</option><option value="mail">Email</option><option value="whatsapp">WhatsApp</option><option value="push">Push</option>
                            </select>
                            <select value={filters.status ?? ''} onChange={(e) => apply({ status: e.target.value })} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                <option value="">All statuses</option>
                                <option value="sent">Sent</option><option value="failed">Failed</option><option value="skipped">Skipped</option>
                            </select>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">When</th>
                                        <th className="py-2 pr-4 font-medium">User</th>
                                        <th className="py-2 pr-4 font-medium">Event</th>
                                        <th className="py-2 pr-4 font-medium">Channel</th>
                                        <th className="py-2 pr-4 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {logs.data.length === 0 ? (
                                        <tr><td colSpan={5} className="py-8 text-center text-muted-foreground">No delivery logs.</td></tr>
                                    ) : logs.data.map((l) => (
                                        <tr key={l.id} className="border-b last:border-0">
                                            <td className="py-2 pr-4 text-xs">{formatDateTime(l.sentAt)}</td>
                                            <td className="py-2 pr-4">{l.user ?? '—'}</td>
                                            <td className="py-2 pr-4 font-mono text-xs">{l.eventKey}</td>
                                            <td className="py-2 pr-4 capitalize">{l.channel}</td>
                                            <td className="py-2 pr-4"><StatusBadge status={l.status} /></td>
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

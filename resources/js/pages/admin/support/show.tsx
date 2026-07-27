import { StatusBadge } from '@/components/status-badge';
import { TicketThread, type TicketMessage } from '@/components/ticket-thread';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';

interface Ticket {
    id: string; reference: string; subject: string; status: string; priority: string;
    customer: string | null; messages: TicketMessage[];
}
interface Props { ticket: Ticket; statuses: string[] }

export default function AdminSupportShow({ ticket, statuses }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Support', href: '/admin/support' },
        { title: ticket.reference, href: `/admin/support/${ticket.id}` },
    ];
    const form = useForm({ message: '' });
    const reply = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/admin/support/${ticket.id}/reply`, { preserveScroll: true, onSuccess: () => form.reset('message') });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={ticket.reference} />
            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">{ticket.subject}</h1>
                        <div className="text-sm text-muted-foreground">{ticket.reference} · {ticket.customer}</div>
                    </div>
                    <div className="flex items-center gap-2">
                        <StatusBadge status={ticket.status} />
                        <select
                            value={ticket.status}
                            onChange={(e) => router.post(`/admin/support/${ticket.id}/status`, { status: e.target.value }, { preserveScroll: true })}
                            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            {statuses.map((s) => <option key={s} value={s}>{s}</option>)}
                        </select>
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Conversation</CardTitle></CardHeader>
                    <CardContent>
                        <TicketThread messages={ticket.messages} />
                        <form onSubmit={reply} className="mt-4 flex gap-2">
                            <textarea value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} rows={2} placeholder="Type a reply…" className="flex-1 rounded-md border border-input bg-background p-2 text-sm" />
                            <Button type="submit" disabled={form.processing || !form.data.message}>Reply</Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

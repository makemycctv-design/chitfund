import { StatusBadge } from '@/components/status-badge';
import { TicketThread, type TicketMessage } from '@/components/ticket-thread';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

interface Ticket {
    id: string; reference: string; subject: string; status: string; priority: string; messages: TicketMessage[];
}

export default function PortalSupportShow({ ticket }: { ticket: Ticket }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Support', href: '/portal/support' },
        { title: ticket.reference, href: `/portal/support/${ticket.id}` },
    ];
    const form = useForm({ message: '' });
    const send = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(`/portal/support/${ticket.id}/message`, { preserveScroll: true, onSuccess: () => form.reset('message') });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={ticket.reference} />
            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">{ticket.subject}</h1>
                        <div className="text-sm text-muted-foreground">{ticket.reference}</div>
                    </div>
                    <StatusBadge status={ticket.status} />
                </div>
                <Card>
                    <CardHeader><CardTitle>Conversation</CardTitle></CardHeader>
                    <CardContent>
                        <TicketThread messages={ticket.messages} />
                        <form onSubmit={send} className="mt-4 flex gap-2">
                            <textarea value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} rows={2} placeholder="Add a message…" className="flex-1 rounded-md border border-input bg-background p-2 text-sm" />
                            <Button type="submit" disabled={form.processing || !form.data.message}>Send</Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/format';
import { useTranslations } from '@/lib/i18n';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';

interface Ticket { id: string; reference: string; subject: string; status: string; priority: string; lastMessageAt: string | null }

export default function PortalSupportIndex({ tickets }: { tickets: Ticket[] }) {
    const { t } = useTranslations();
    const breadcrumbs: BreadcrumbItem[] = [{ title: t('nav.support'), href: '/portal/support' }];
    const form = useForm({ subject: '', category: '', priority: 'normal', message: '' });
    const submit = (e: React.FormEvent) => { e.preventDefault(); form.post('/portal/support', { onSuccess: () => form.reset() }); };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('nav.support')} />
            <div className="grid flex-1 gap-4 p-4 lg:grid-cols-3">
                <Card className="lg:col-span-1">
                    <CardHeader><CardTitle>New Ticket</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-3">
                            <div className="grid gap-1.5"><Label>Subject</Label><Input value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} />{form.errors.subject && <p className="text-xs text-destructive">{form.errors.subject}</p>}</div>
                            <div className="grid gap-1.5">
                                <Label>Priority</Label>
                                <select value={form.data.priority} onChange={(e) => form.setData('priority', e.target.value)} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                    <option value="low">Low</option><option value="normal">Normal</option><option value="high">High</option>
                                </select>
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Message</Label>
                                <textarea value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} rows={4} className="rounded-md border border-input bg-background p-2 text-sm" />
                                {form.errors.message && <p className="text-xs text-destructive">{form.errors.message}</p>}
                            </div>
                            <Button type="submit" disabled={form.processing}>Submit</Button>
                        </form>
                    </CardContent>
                </Card>
                <Card className="lg:col-span-2">
                    <CardHeader><CardTitle>My Tickets</CardTitle></CardHeader>
                    <CardContent>
                        {tickets.length === 0 ? <p className="py-8 text-center text-sm text-muted-foreground">No tickets yet.</p> : (
                            <ul className="space-y-2">
                                {tickets.map((t) => (
                                    <li key={t.id} className="flex items-center justify-between rounded-md border p-3">
                                        <div>
                                            <Link href={`/portal/support/${t.id}`} className="font-medium hover:underline">{t.subject}</Link>
                                            <div className="text-xs text-muted-foreground">{t.reference} · {formatDateTime(t.lastMessageAt)}</div>
                                        </div>
                                        <StatusBadge status={t.status} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

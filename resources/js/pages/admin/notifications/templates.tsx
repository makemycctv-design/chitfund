import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

interface Template {
    id: string; eventKey: string; channel: string; name: string;
    subject: string | null; body: string; isActive: boolean; isGlobal: boolean;
    whatsappApprovalStatus: string | null;
}
interface Props { templates: Template[] }

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notification Templates', href: '/admin/notification-templates' }];

export default function Templates({ templates }: Props) {
    const [editing, setEditing] = useState<Template | null>(null);
    const form = useForm<{ event_key: string; channel: string; name: string; subject: string; body: string; is_active: boolean }>({
        event_key: '', channel: '', name: '', subject: '', body: '', is_active: true,
    });

    const open = (t: Template) => {
        setEditing(t);
        form.setData({ event_key: t.eventKey, channel: t.channel, name: t.name, subject: t.subject ?? '', body: t.body, is_active: t.isActive });
    };
    const save = (e: React.FormEvent) => {
        e.preventDefault();
        // Company override is created/updated via store (updateOrCreate on event+channel).
        form.post('/admin/notification-templates', { preserveScroll: true, onSuccess: () => setEditing(null) });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notification Templates" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Notification Templates</h1>
                <Card>
                    <CardContent className="pt-6">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">Event</th>
                                        <th className="py-2 pr-4 font-medium">Channel</th>
                                        <th className="py-2 pr-4 font-medium">Scope</th>
                                        <th className="py-2 pr-4 font-medium">Active</th>
                                        <th className="py-2 pr-4 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {templates.map((t) => (
                                        <tr key={t.id} className="border-b last:border-0">
                                            <td className="py-2 pr-4 font-mono text-xs">{t.eventKey}</td>
                                            <td className="py-2 pr-4 capitalize">{t.channel}</td>
                                            <td className="py-2 pr-4">{t.isGlobal ? <span className="text-xs text-muted-foreground">Global default</span> : <span className="text-xs text-primary">Company override</span>}</td>
                                            <td className="py-2 pr-4"><StatusBadge status={t.isActive ? 'active' : 'rejected'} label={t.isActive ? 'On' : 'Off'} /></td>
                                            <td className="py-2 pr-4 text-right"><Button size="sm" variant="ghost" onClick={() => open(t)}>{t.isGlobal ? 'Override' : 'Edit'}</Button></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={!!editing} onOpenChange={(o) => !o && setEditing(null)}>
                <DialogContent>
                    <DialogHeader><DialogTitle>Edit Template — {editing?.eventKey} / {editing?.channel}</DialogTitle></DialogHeader>
                    <form onSubmit={save} className="grid gap-3">
                        <div className="grid gap-1.5"><Label>Name</Label><Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} /></div>
                        <div className="grid gap-1.5"><Label>Subject / Title</Label><Input value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} /></div>
                        <div className="grid gap-1.5">
                            <Label>Body <span className="text-xs text-muted-foreground">(use {'{{'}name{'}}'} tokens)</span></Label>
                            <textarea value={form.data.body} onChange={(e) => form.setData('body', e.target.value)} rows={5} className="rounded-md border border-input bg-background p-2 text-sm" />
                        </div>
                        <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} /> Active</label>
                        <DialogFooter><Button type="submit" disabled={form.processing}>Save Company Template</Button></DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}

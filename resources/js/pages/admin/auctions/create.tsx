import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { useMemo } from 'react';

interface Period { periodNo: number; dueDate: string | null }
interface ChittyOption { id: string; label: string; periods: Period[] }

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Auctions', href: '/admin/auctions' },
    { title: 'Schedule', href: '/admin/auctions/create' },
];

export default function ScheduleAuction({ chitties }: { chitties: ChittyOption[] }) {
    const { data, setData, post, processing, errors } = useForm({
        chitty_id: '',
        period_no: '',
        scheduled_at: '',
    });

    const selected = useMemo(() => chitties.find((c) => c.id === data.chitty_id), [chitties, data.chitty_id]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/auctions');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Schedule Auction" />
            <form onSubmit={submit} className="mx-auto w-full max-w-xl flex-1 p-4">
                <Card>
                    <CardHeader><CardTitle>Schedule Auction</CardTitle></CardHeader>
                    <CardContent className="grid gap-4">
                        {chitties.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No chitties with an available (un-auctioned) period. Generate a chitty schedule first.
                            </p>
                        ) : (
                            <>
                                <div className="grid gap-1.5">
                                    <Label>Chitty</Label>
                                    <select
                                        value={data.chitty_id}
                                        onChange={(e) => { setData('chitty_id', e.target.value); setData('period_no', ''); }}
                                        className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="">— Select —</option>
                                        {chitties.map((c) => (<option key={c.id} value={c.id}>{c.label}</option>))}
                                    </select>
                                    {errors.chitty_id && <p className="text-xs text-destructive">{errors.chitty_id}</p>}
                                </div>

                                <div className="grid gap-1.5">
                                    <Label>Period</Label>
                                    <select
                                        value={data.period_no}
                                        onChange={(e) => setData('period_no', e.target.value)}
                                        disabled={!selected}
                                        className="h-9 rounded-md border border-input bg-background px-3 text-sm disabled:opacity-50"
                                    >
                                        <option value="">— Select —</option>
                                        {selected?.periods.map((p) => (
                                            <option key={p.periodNo} value={p.periodNo}>
                                                Period #{p.periodNo} (due {formatDate(p.dueDate)})
                                            </option>
                                        ))}
                                    </select>
                                    {errors.period_no && <p className="text-xs text-destructive">{errors.period_no}</p>}
                                </div>

                                <div className="grid gap-1.5">
                                    <Label>Scheduled At</Label>
                                    <Input type="datetime-local" value={data.scheduled_at} onChange={(e) => setData('scheduled_at', e.target.value)} />
                                    {errors.scheduled_at && <p className="text-xs text-destructive">{errors.scheduled_at}</p>}
                                </div>

                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Scheduling…' : 'Schedule Auction'}
                                </Button>
                            </>
                        )}
                    </CardContent>
                </Card>
            </form>
        </AppLayout>
    );
}

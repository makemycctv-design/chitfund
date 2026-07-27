import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';

interface Scheme {
    id: string;
    code: string;
    name: string;
    chitValue: number;
    durationMonths: number;
    installmentAmount: number;
    foremanCommissionPercent: number;
    isActive: boolean;
    chittiesCount: number;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Schemes', href: '/admin/schemes' }];

export default function SchemesIndex({ schemes }: { schemes: Scheme[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        name: '',
        chit_value: '',
        duration_months: '',
        total_subscribers: '',
        installment_amount: '',
        foreman_commission_percent: '5',
        auction_frequency: 'monthly',
        grace_period_days: '5',
        late_fee_type: 'percent',
        late_fee_value: '2',
        required_kyc_level: '1',
        is_active: true,
    });

    // Auto-derive installment when value + duration are set.
    const deriveInstallment = (value: string, months: string) => {
        const v = parseFloat(value);
        const m = parseInt(months);
        if (v > 0 && m > 0) setData('installment_amount', (v / m).toFixed(2));
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/schemes', { preserveScroll: true, onSuccess: () => reset() });
    };

    const remove = (s: Scheme) => {
        if (confirm(`Delete scheme ${s.code}?`)) {
            router.delete(`/admin/schemes/${s.id}`, { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Schemes" />
            <div className="grid flex-1 gap-4 p-4 lg:grid-cols-3">
                {/* Create form */}
                <Card className="lg:col-span-1">
                    <CardHeader>
                        <CardTitle>New Scheme</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-3">
                            <Field label="Code" error={errors.code}>
                                <Input value={data.code} onChange={(e) => setData('code', e.target.value)} />
                            </Field>
                            <Field label="Name" error={errors.name}>
                                <Input value={data.name} onChange={(e) => setData('name', e.target.value)} />
                            </Field>
                            <Field label="Chit Value (₹)" error={errors.chit_value}>
                                <Input
                                    type="number"
                                    step="0.01"
                                    value={data.chit_value}
                                    onChange={(e) => {
                                        setData('chit_value', e.target.value);
                                        deriveInstallment(e.target.value, data.duration_months);
                                    }}
                                />
                            </Field>
                            <Field label="Duration (months)" error={errors.duration_months}>
                                <Input
                                    type="number"
                                    value={data.duration_months}
                                    onChange={(e) => {
                                        setData('duration_months', e.target.value);
                                        setData('total_subscribers', e.target.value);
                                        deriveInstallment(data.chit_value, e.target.value);
                                    }}
                                />
                            </Field>
                            <Field label="Total Subscribers" error={errors.total_subscribers}>
                                <Input type="number" value={data.total_subscribers} onChange={(e) => setData('total_subscribers', e.target.value)} />
                            </Field>
                            <Field label="Installment (₹)" error={errors.installment_amount}>
                                <Input type="number" step="0.01" value={data.installment_amount} onChange={(e) => setData('installment_amount', e.target.value)} />
                            </Field>
                            <Field label="Foreman Commission (%)" error={errors.foreman_commission_percent}>
                                <Input type="number" step="0.01" value={data.foreman_commission_percent} onChange={(e) => setData('foreman_commission_percent', e.target.value)} />
                            </Field>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Saving…' : 'Create Scheme'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* List */}
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Schemes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">Scheme</th>
                                        <th className="py-2 pr-4 font-medium">Value</th>
                                        <th className="py-2 pr-4 font-medium">Months</th>
                                        <th className="py-2 pr-4 font-medium">Installment</th>
                                        <th className="py-2 pr-4 font-medium">Chitties</th>
                                        <th className="py-2 pr-4 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {schemes.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="py-8 text-center text-muted-foreground">
                                                No schemes yet.
                                            </td>
                                        </tr>
                                    ) : (
                                        schemes.map((s) => (
                                            <tr key={s.id} className="border-b last:border-0">
                                                <td className="py-2 pr-4">
                                                    <div className="font-medium">{s.name}</div>
                                                    <div className="text-xs text-muted-foreground">{s.code}</div>
                                                </td>
                                                <td className="py-2 pr-4">{formatCurrency(s.chitValue)}</td>
                                                <td className="py-2 pr-4">{s.durationMonths}</td>
                                                <td className="py-2 pr-4">{formatCurrency(s.installmentAmount)}</td>
                                                <td className="py-2 pr-4">{s.chittiesCount}</td>
                                                <td className="py-2 pr-4 text-right">
                                                    <Button variant="ghost" size="icon" onClick={() => remove(s)} disabled={s.chittiesCount > 0}>
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </Button>
                                                </td>
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

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return (
        <div className="grid gap-1.5">
            <Label>{label}</Label>
            {children}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}

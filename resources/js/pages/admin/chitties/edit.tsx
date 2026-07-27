import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { SelectOption } from '@/types/pagination';
import { Head, useForm } from '@inertiajs/react';

interface ChittyData {
    id: string;
    code: string;
    name: string;
    branch_id: number;
    chitty_scheme_id: number | null;
    chit_value: string | number;
    duration_months: number;
    total_subscribers: number;
    installment_amount: string | number;
    foreman_commission_percent: string | number;
    auction_frequency: string;
    min_bid_percent: string | number | null;
    max_bid_percent: string | number | null;
    grace_period_days: number;
    late_fee_type: string;
    late_fee_value: string | number;
    required_kyc_level: number;
    auction_day: number | null;
    start_date: string | null;
    maturity_date: string | null;
    notes: string | null;
    terms: string | null;
    [key: string]: string | number | boolean | null;
}

interface Props {
    chitty: ChittyData;
    branches: SelectOption[];
    schemes: SelectOption[];
}

export default function EditChitty({ chitty, branches }: Props) {
    const { data, setData, put, processing, errors } = useForm<ChittyData>({ ...chitty });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Chitties', href: '/admin/chitties' },
        { title: chitty.code, href: `/admin/chitties/${chitty.id}` },
        { title: 'Edit', href: `/admin/chitties/${chitty.id}/edit` },
    ];

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/chitties/${chitty.id}`);
    };

    const num = (k: keyof ChittyData, label: string, step?: string) => (
        <div className="grid gap-1.5">
            <Label htmlFor={String(k)}>{label}</Label>
            <Input
                id={String(k)}
                type="number"
                step={step}
                value={(data[k] as number | string | null) ?? ''}
                onChange={(e) => setData(k, e.target.value as never)}
            />
            {errors[k] && <p className="text-xs text-destructive">{errors[k] as string}</p>}
        </div>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${chitty.code}`} />
            <form onSubmit={submit} className="mx-auto w-full max-w-3xl flex-1 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Edit Chitty</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-1.5">
                            <Label htmlFor="branch_id">Branch</Label>
                            <select
                                id="branch_id"
                                value={data.branch_id}
                                onChange={(e) => setData('branch_id', Number(e.target.value))}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                {branches.map((b) => (
                                    <option key={b.value} value={b.value}>
                                        {b.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="code">Code</Label>
                            <Input id="code" value={data.code} onChange={(e) => setData('code', e.target.value)} />
                            {errors.code && <p className="text-xs text-destructive">{errors.code}</p>}
                        </div>
                        <div className="grid gap-1.5 sm:col-span-2">
                            <Label htmlFor="name">Name</Label>
                            <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                            {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                        </div>
                        {num('chit_value', 'Chit Value (₹)', '0.01')}
                        {num('installment_amount', 'Installment (₹)', '0.01')}
                        {num('duration_months', 'Duration (months)')}
                        {num('total_subscribers', 'Total Subscribers')}
                        {num('foreman_commission_percent', 'Foreman Commission (%)', '0.01')}
                        {num('grace_period_days', 'Grace Period (days)')}
                        <div className="grid gap-1.5">
                            <Label htmlFor="late_fee_type">Late Fee Type</Label>
                            <select
                                id="late_fee_type"
                                value={data.late_fee_type}
                                onChange={(e) => setData('late_fee_type', e.target.value)}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="none">None</option>
                                <option value="fixed">Fixed</option>
                                <option value="percent">Percent</option>
                            </select>
                        </div>
                        {num('late_fee_value', 'Late Fee Value', '0.01')}
                        {num('auction_day', 'Auction Day (1-28)')}
                        {num('required_kyc_level', 'Required KYC Level')}
                        <div className="grid gap-1.5">
                            <Label htmlFor="start_date">Start Date</Label>
                            <Input
                                id="start_date"
                                type="date"
                                value={data.start_date ?? ''}
                                onChange={(e) => setData('start_date', e.target.value)}
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="maturity_date">Maturity Date</Label>
                            <Input
                                id="maturity_date"
                                type="date"
                                value={data.maturity_date ?? ''}
                                onChange={(e) => setData('maturity_date', e.target.value)}
                            />
                        </div>
                    </CardContent>
                </Card>
                <div className="mt-4 flex justify-end">
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Saving…' : 'Save Changes'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}

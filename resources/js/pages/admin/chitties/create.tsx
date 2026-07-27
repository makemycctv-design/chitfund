import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import type { SelectOption } from '@/types/pagination';
import { zodResolver } from '@hookform/resolvers/zod';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';

interface SchemeOption extends SelectOption {
    defaults: Record<string, number | string | null>;
}

interface Props {
    branches: SelectOption[];
    schemes: SchemeOption[];
}

const schema = z.object({
    branch_id: z.coerce.number().min(1, 'Select a branch'),
    chitty_scheme_id: z.coerce.number().optional().nullable(),
    code: z.string().min(1, 'Code is required').max(50),
    name: z.string().min(1, 'Name is required').max(255),
    chit_value: z.coerce.number().gt(0, 'Must be greater than 0'),
    duration_months: z.coerce.number().min(2).max(120),
    total_subscribers: z.coerce.number().min(2).max(500),
    installment_amount: z.coerce.number().gt(0),
    foreman_commission_percent: z.coerce.number().min(0).max(100),
    auction_frequency: z.enum(['monthly', 'fortnightly', 'weekly']),
    min_bid_percent: z.coerce.number().min(0).max(100).optional(),
    max_bid_percent: z.coerce.number().min(0).max(100).optional(),
    grace_period_days: z.coerce.number().min(0).max(60),
    late_fee_type: z.enum(['none', 'fixed', 'percent']),
    late_fee_value: z.coerce.number().min(0),
    required_kyc_level: z.coerce.number().min(0).max(3),
    start_date: z.string().optional(),
    maturity_date: z.string().optional(),
    auction_day: z.coerce.number().min(1).max(28).optional(),
});

type FormValues = z.input<typeof schema>;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Chitties', href: '/admin/chitties' },
    { title: 'New', href: '/admin/chitties/create' },
];

const STEPS = ['Basics', 'Financials', 'Schedule & Rules'];

export default function CreateChitty({ branches, schemes }: Props) {
    const [step, setStep] = useState(0);
    const [submitting, setSubmitting] = useState(false);

    const {
        register,
        handleSubmit,
        setValue,
        trigger,
        setError,
        formState: { errors },
    } = useForm<FormValues>({
        resolver: zodResolver(schema),
        defaultValues: {
            auction_frequency: 'monthly',
            late_fee_type: 'percent',
            late_fee_value: 2,
            foreman_commission_percent: 5,
            grace_period_days: 5,
            required_kyc_level: 1,
            min_bid_percent: 0,
            max_bid_percent: 40,
        },
    });

    const applyScheme = (id: string) => {
        setValue('chitty_scheme_id', id ? Number(id) : null);
        const scheme = schemes.find((s) => String(s.value) === id);
        if (scheme) {
            Object.entries(scheme.defaults).forEach(([k, v]) => {
                if (v !== null) setValue(k as keyof FormValues, v as never);
            });
        }
    };

    const stepFields: (keyof FormValues)[][] = [
        ['branch_id', 'code', 'name'],
        ['chit_value', 'duration_months', 'total_subscribers', 'installment_amount', 'foreman_commission_percent'],
        ['auction_frequency', 'grace_period_days', 'late_fee_type', 'late_fee_value', 'required_kyc_level'],
    ];

    const next = async () => {
        const valid = await trigger(stepFields[step]);
        if (valid) setStep((s) => Math.min(s + 1, STEPS.length - 1));
    };

    const onSubmit = (values: FormValues) => {
        setSubmitting(true);
        router.post('/admin/chitties', values as never, {
            onError: (errs) => {
                Object.entries(errs).forEach(([k, v]) => setError(k as keyof FormValues, { message: v as string }));
                setSubmitting(false);
            },
            onFinish: () => setSubmitting(false),
        });
    };

    const field = (name: keyof FormValues, label: string, type = 'text', extra?: React.InputHTMLAttributes<HTMLInputElement>) => (
        <div className="grid gap-1.5">
            <Label htmlFor={name}>{label}</Label>
            <Input id={name} type={type} {...register(name)} {...extra} />
            {errors[name] && <p className="text-xs text-destructive">{errors[name]?.message as string}</p>}
        </div>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Chitty" />

            <div className="mx-auto w-full max-w-3xl flex-1 p-4">
                {/* Stepper */}
                <div className="mb-6 flex items-center gap-2">
                    {STEPS.map((s, i) => (
                        <div key={s} className="flex flex-1 items-center gap-2">
                            <div
                                className={cn(
                                    'flex h-7 w-7 items-center justify-center rounded-full text-xs font-medium',
                                    i <= step ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground',
                                )}
                            >
                                {i + 1}
                            </div>
                            <span className={cn('text-sm', i === step ? 'font-medium' : 'text-muted-foreground')}>{s}</span>
                            {i < STEPS.length - 1 && <div className="h-px flex-1 bg-border" />}
                        </div>
                    ))}
                </div>

                <form onSubmit={handleSubmit(onSubmit)}>
                    <Card>
                        <CardHeader>
                            <CardTitle>{STEPS[step]}</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            {step === 0 && (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="chitty_scheme_id">Scheme (optional — prefills financials)</Label>
                                        <select
                                            id="chitty_scheme_id"
                                            onChange={(e) => applyScheme(e.target.value)}
                                            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            <option value="">— None —</option>
                                            {schemes.map((s) => (
                                                <option key={s.value} value={s.value}>
                                                    {s.label}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="branch_id">Branch</Label>
                                        <select
                                            id="branch_id"
                                            {...register('branch_id')}
                                            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            <option value="">— Select —</option>
                                            {branches.map((b) => (
                                                <option key={b.value} value={b.value}>
                                                    {b.label}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.branch_id && <p className="text-xs text-destructive">{errors.branch_id.message}</p>}
                                    </div>
                                    {field('code', 'Chitty Code')}
                                    {field('name', 'Chitty Name')}
                                </>
                            )}

                            {step === 1 && (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {field('chit_value', 'Total Chit Value (₹)', 'number', { step: '0.01' })}
                                    {field('installment_amount', 'Monthly Installment (₹)', 'number', { step: '0.01' })}
                                    {field('duration_months', 'Duration (months)', 'number')}
                                    {field('total_subscribers', 'Total Subscribers', 'number')}
                                    {field('foreman_commission_percent', 'Foreman Commission (%)', 'number', { step: '0.01' })}
                                </div>
                            )}

                            {step === 2 && (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="auction_frequency">Auction Frequency</Label>
                                        <select
                                            id="auction_frequency"
                                            {...register('auction_frequency')}
                                            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            <option value="monthly">Monthly</option>
                                            <option value="fortnightly">Fortnightly</option>
                                            <option value="weekly">Weekly</option>
                                        </select>
                                    </div>
                                    {field('grace_period_days', 'Grace Period (days)', 'number')}
                                    <div className="grid gap-1.5">
                                        <Label htmlFor="late_fee_type">Late Fee Type</Label>
                                        <select
                                            id="late_fee_type"
                                            {...register('late_fee_type')}
                                            className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                                        >
                                            <option value="none">None</option>
                                            <option value="fixed">Fixed (₹)</option>
                                            <option value="percent">Percent (%)</option>
                                        </select>
                                    </div>
                                    {field('late_fee_value', 'Late Fee Value', 'number', { step: '0.01' })}
                                    {field('min_bid_percent', 'Min Bid (%)', 'number', { step: '0.01' })}
                                    {field('max_bid_percent', 'Max Bid (%)', 'number', { step: '0.01' })}
                                    {field('required_kyc_level', 'Required KYC Level', 'number')}
                                    {field('auction_day', 'Auction Day (1-28)', 'number')}
                                    {field('start_date', 'Start Date', 'date')}
                                    {field('maturity_date', 'Maturity Date', 'date')}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="mt-4 flex justify-between">
                        <Button type="button" variant="outline" disabled={step === 0} onClick={() => setStep((s) => s - 1)}>
                            Back
                        </Button>
                        {step < STEPS.length - 1 ? (
                            <Button type="button" onClick={next}>
                                Next
                            </Button>
                        ) : (
                            <Button type="submit" disabled={submitting}>
                                {submitting ? 'Creating…' : 'Create Chitty'}
                            </Button>
                        )}
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

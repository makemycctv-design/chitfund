import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { SelectOption } from '@/types/pagination';
import { Head, useForm } from '@inertiajs/react';

interface Props { roles: SelectOption[]; branches: SelectOption[] }

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Staff', href: '/admin/staff' },
    { title: 'Add', href: '/admin/staff/create' },
];

export default function StaffCreate({ roles, branches }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '', email: '', password: '', role: roles[0]?.value ?? '', branch_id: '', designation: '',
    });

    const submit = (e: React.FormEvent) => { e.preventDefault(); post('/admin/staff'); };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Staff" />
            <form onSubmit={submit} className="mx-auto w-full max-w-xl flex-1 p-4">
                <Card>
                    <CardHeader><CardTitle>New Staff Account</CardTitle></CardHeader>
                    <CardContent className="grid gap-4">
                        <div className="grid gap-1.5"><Label>Name</Label><Input value={data.name} onChange={(e) => setData('name', e.target.value)} />{errors.name && <p className="text-xs text-destructive">{errors.name}</p>}</div>
                        <div className="grid gap-1.5"><Label>Email</Label><Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />{errors.email && <p className="text-xs text-destructive">{errors.email}</p>}</div>
                        <div className="grid gap-1.5"><Label>Temporary Password</Label><Input type="text" value={data.password} onChange={(e) => setData('password', e.target.value)} />{errors.password && <p className="text-xs text-destructive">{errors.password}</p>}</div>
                        <div className="grid gap-1.5">
                            <Label>Role</Label>
                            <select value={data.role} onChange={(e) => setData('role', e.target.value)} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                {roles.map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
                            </select>
                            {errors.role && <p className="text-xs text-destructive">{errors.role}</p>}
                        </div>
                        <div className="grid gap-1.5">
                            <Label>Branch (optional)</Label>
                            <select value={data.branch_id} onChange={(e) => setData('branch_id', e.target.value)} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                <option value="">— None —</option>
                                {branches.map((b) => <option key={b.value} value={b.value}>{b.label}</option>)}
                            </select>
                        </div>
                        <div className="grid gap-1.5"><Label>Designation</Label><Input value={data.designation} onChange={(e) => setData('designation', e.target.value)} /></div>
                        <Button type="submit" disabled={processing}>{processing ? 'Creating…' : 'Create Staff'}</Button>
                    </CardContent>
                </Card>
            </form>
        </AppLayout>
    );
}

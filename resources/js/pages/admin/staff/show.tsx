import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { SelectOption } from '@/types/pagination';
import { Head, router, useForm } from '@inertiajs/react';

interface Staff {
    id: string; name: string; email: string; phone: string | null; roles: string[];
    designation: string | null; branch: string | null; branchId: number | null; status: string; lastLoginAt: string | null;
}
interface Props {
    staff: Staff;
    loginHistory: { ip: string | null; at: string | null; agent: string | null }[];
    activity: { description: string; at: string | null }[];
    roles: SelectOption[];
    branches: SelectOption[];
    can: { manage: boolean; assignRoles: boolean };
}

export default function StaffShow({ staff, loginHistory, activity, roles, branches, can }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Staff', href: '/admin/staff' },
        { title: staff.name, href: `/admin/staff/${staff.id}` },
    ];
    const form = useForm({ role: staff.roles[0] ?? '', branch_id: staff.branchId ?? '', designation: staff.designation ?? '' });

    const save = (e: React.FormEvent) => { e.preventDefault(); form.put(`/admin/staff/${staff.id}`, { preserveScroll: true }); };
    const suspend = () => router.post(`/admin/staff/${staff.id}/suspend`, {}, { preserveScroll: true });
    const reactivate = () => router.post(`/admin/staff/${staff.id}/reactivate`, {}, { preserveScroll: true });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={staff.name} />
            <div className="grid flex-1 gap-4 p-4 lg:grid-cols-3">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>{staff.name}</CardTitle>
                        <StatusBadge status={staff.status === 'active' ? 'active' : 'rejected'} label={staff.status} />
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <div className="flex justify-between"><span className="text-muted-foreground">Email</span><span>{staff.email}</span></div>
                        <div className="flex justify-between"><span className="text-muted-foreground">Branch</span><span>{staff.branch ?? '—'}</span></div>
                        <div className="flex justify-between"><span className="text-muted-foreground">Last login</span><span>{formatDateTime(staff.lastLoginAt)}</span></div>
                        {can.manage && (
                            <div className="pt-3">
                                {staff.status === 'active'
                                    ? <Button variant="outline" size="sm" onClick={suspend}>Suspend</Button>
                                    : <Button variant="secondary" size="sm" onClick={reactivate}>Reactivate</Button>}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {can.manage && (
                    <Card>
                        <CardHeader><CardTitle>Role & Assignment</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={save} className="grid gap-3">
                                <div className="grid gap-1.5">
                                    <Label>Role</Label>
                                    <select value={form.data.role} onChange={(e) => form.setData('role', e.target.value)} disabled={!can.assignRoles} className="h-9 rounded-md border border-input bg-background px-3 text-sm disabled:opacity-50">
                                        {roles.map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
                                    </select>
                                </div>
                                <div className="grid gap-1.5">
                                    <Label>Branch</Label>
                                    <select value={form.data.branch_id} onChange={(e) => form.setData('branch_id', e.target.value === '' ? '' : Number(e.target.value))} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                        <option value="">— None —</option>
                                        {branches.map((b) => <option key={b.value} value={b.value}>{b.label}</option>)}
                                    </select>
                                </div>
                                <div className="grid gap-1.5"><Label>Designation</Label><Input value={form.data.designation} onChange={(e) => form.setData('designation', e.target.value)} /></div>
                                <Button type="submit" size="sm" disabled={form.processing}>Save</Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader><CardTitle>Login History</CardTitle></CardHeader>
                    <CardContent>
                        {loginHistory.length === 0 ? <p className="text-sm text-muted-foreground">No logins recorded.</p> : (
                            <ul className="space-y-1 text-xs">
                                {loginHistory.map((l, i) => (
                                    <li key={i} className="flex justify-between border-b border-border/40 py-1">
                                        <span>{l.ip ?? '—'}</span><span className="text-muted-foreground">{formatDateTime(l.at)}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <h3 className="mt-4 mb-1 text-sm font-medium">Recent Activity</h3>
                        <ul className="space-y-1 text-xs">
                            {activity.map((a, i) => (
                                <li key={i} className="flex justify-between border-b border-border/40 py-1">
                                    <span className="capitalize">{a.description}</span><span className="text-muted-foreground">{formatDateTime(a.at)}</span>
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

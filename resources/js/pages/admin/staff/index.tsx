import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';

interface StaffRow {
    id: string;
    name: string;
    email: string;
    roles: string[];
    designation: string | null;
    status: string;
    isActive: boolean;
}
interface Props {
    staff: Paginated<StaffRow>;
    filters: { search?: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Staff', href: '/admin/staff' }];

export default function StaffIndex({ staff, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Staff" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">Staff</h1>
                    <Button asChild><Link href="/admin/staff/create"><Plus className="mr-1 h-4 w-4" /> Add Staff</Link></Button>
                </div>
                <Card>
                    <CardContent className="pt-6">
                        <form className="mb-4 flex gap-2" onSubmit={(e) => { e.preventDefault(); router.get('/admin/staff', { search }, { preserveState: true, replace: true }); }}>
                            <Input placeholder="Search name or email…" value={search} onChange={(e) => setSearch(e.target.value)} className="max-w-xs" />
                            <Button type="submit" variant="secondary">Search</Button>
                        </form>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">Name</th>
                                        <th className="py-2 pr-4 font-medium">Role</th>
                                        <th className="py-2 pr-4 font-medium">Designation</th>
                                        <th className="py-2 pr-4 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {staff.data.length === 0 ? (
                                        <tr><td colSpan={4} className="py-8 text-center text-muted-foreground">No staff found.</td></tr>
                                    ) : staff.data.map((s) => (
                                        <tr key={s.id} className="border-b last:border-0 hover:bg-muted/40">
                                            <td className="py-3 pr-4">
                                                <Link href={`/admin/staff/${s.id}`} className="font-medium hover:underline">{s.name}</Link>
                                                <div className="text-xs text-muted-foreground">{s.email}</div>
                                            </td>
                                            <td className="py-3 pr-4 capitalize">{s.roles.join(', ').replace(/-/g, ' ')}</td>
                                            <td className="py-3 pr-4">{s.designation ?? '—'}</td>
                                            <td className="py-3 pr-4"><StatusBadge status={s.isActive ? 'active' : 'rejected'} label={s.isActive ? 'Active' : 'Suspended'} /></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={staff.links} from={staff.from} to={staff.to} total={staff.total} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

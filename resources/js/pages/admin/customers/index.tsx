import { Pagination } from '@/components/pagination';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/pagination';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

interface CustomerRow {
    id: string;
    name: string;
    email: string;
    phone: string | null;
    customerCode: string | null;
    registrationStatus: string | null;
    kycStatus: string | null;
}

interface Props {
    customers: Paginated<CustomerRow>;
    filters: { search?: string; registration?: string; kyc?: string };
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Customers', href: '/admin/customers' }];

export default function CustomersIndex({ customers, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const apply = (patch: Record<string, string>) =>
        router.get('/admin/customers', { ...filters, ...patch }, { preserveState: true, replace: true });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Customers" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Customers</h1>
                <Card>
                    <CardContent className="pt-6">
                        <form
                            className="mb-4 flex flex-wrap gap-2"
                            onSubmit={(e) => {
                                e.preventDefault();
                                apply({ search });
                            }}
                        >
                            <Input placeholder="Search name, email, phone…" value={search} onChange={(e) => setSearch(e.target.value)} className="max-w-xs" />
                            <select value={filters.registration ?? ''} onChange={(e) => apply({ registration: e.target.value })} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                <option value="">All registrations</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <select value={filters.kyc ?? ''} onChange={(e) => apply({ kyc: e.target.value })} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                <option value="">All KYC</option>
                                <option value="pending">Pending</option>
                                <option value="submitted">Submitted</option>
                                <option value="verified">Verified</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <Button type="submit" variant="secondary">Search</Button>
                        </form>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left text-muted-foreground">
                                        <th className="py-2 pr-4 font-medium">Customer</th>
                                        <th className="py-2 pr-4 font-medium">Code</th>
                                        <th className="py-2 pr-4 font-medium">Registration</th>
                                        <th className="py-2 pr-4 font-medium">KYC</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {customers.data.length === 0 ? (
                                        <tr><td colSpan={4} className="py-8 text-center text-muted-foreground">No customers found.</td></tr>
                                    ) : (
                                        customers.data.map((c) => (
                                            <tr key={c.id} className="border-b last:border-0 hover:bg-muted/40">
                                                <td className="py-3 pr-4">
                                                    <Link href={`/admin/customers/${c.id}`} className="font-medium hover:underline">{c.name}</Link>
                                                    <div className="text-xs text-muted-foreground">{c.email}{c.phone ? ` · ${c.phone}` : ''}</div>
                                                </td>
                                                <td className="py-3 pr-4">{c.customerCode ?? '—'}</td>
                                                <td className="py-3 pr-4"><StatusBadge status={c.registrationStatus} /></td>
                                                <td className="py-3 pr-4"><StatusBadge status={c.kycStatus} /></td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Pagination links={customers.links} from={customers.from} to={customers.to} total={customers.total} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

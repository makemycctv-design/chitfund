import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Download, XCircle } from 'lucide-react';

interface Customer { id: string; name: string; email: string; phone: string | null; isActive: boolean }
interface Profile {
    customerCode: string | null;
    registrationStatus: string;
    kycStatus: string;
    kycLevel: number;
    occupation: string | null;
    city: string | null;
    state: string | null;
    rejectionReason: string | null;
}
interface Doc { id: string; type: string; typeLabel: string; originalName: string; status: string; rejectionReason: string | null; uploadedAt: string | null }
interface Membership { chittyCode: string | null; chittyName: string | null; ticketNumber: number | null; status: string }
interface Props {
    customer: Customer;
    profile: Profile;
    documents: Doc[];
    memberships: Membership[];
    can: { approveRegistration: boolean; verifyKyc: boolean };
}

export default function CustomerShow({ customer, profile, documents, memberships, can }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Customers', href: '/admin/customers' },
        { title: customer.name, href: `/admin/customers/${customer.id}` },
    ];

    const post = (url: string, data: Record<string, string> = {}) => router.post(url, data, { preserveScroll: true });

    const rejectRegistration = () => {
        const reason = prompt('Reason for rejecting registration?');
        if (reason) post(`/admin/customers/${customer.id}/reject`, { reason });
    };
    const rejectDoc = (id: string) => {
        const reason = prompt('Reason for rejecting this document?');
        if (reason) post(`/admin/kyc-documents/${id}/reject`, { reason });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={customer.name} />
            <div className="grid flex-1 gap-4 p-4 lg:grid-cols-3">
                {/* Profile + actions */}
                <Card>
                    <CardHeader>
                        <CardTitle>{customer.name}</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <Row label="Email" value={customer.email} />
                        <Row label="Phone" value={customer.phone ?? '—'} />
                        <Row label="Code" value={profile.customerCode ?? '—'} />
                        <Row label="Occupation" value={profile.occupation ?? '—'} />
                        <Row label="Location" value={[profile.city, profile.state].filter(Boolean).join(', ') || '—'} />
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">Registration</span>
                            <StatusBadge status={profile.registrationStatus} />
                        </div>
                        <div className="flex items-center justify-between">
                            <span className="text-muted-foreground">KYC</span>
                            <StatusBadge status={profile.kycStatus} />
                        </div>
                        {profile.rejectionReason && <p className="text-xs text-destructive">Reason: {profile.rejectionReason}</p>}

                        <div className="flex flex-wrap gap-2 pt-3">
                            {can.approveRegistration && profile.registrationStatus !== 'approved' && (
                                <Button size="sm" onClick={() => post(`/admin/customers/${customer.id}/approve`)}>
                                    Approve Registration
                                </Button>
                            )}
                            {can.approveRegistration && profile.registrationStatus !== 'rejected' && (
                                <Button size="sm" variant="outline" onClick={rejectRegistration}>
                                    Reject
                                </Button>
                            )}
                            {can.verifyKyc && profile.kycStatus !== 'verified' && (
                                <Button size="sm" variant="secondary" onClick={() => post(`/admin/customers/${customer.id}/verify-kyc`)}>
                                    Verify KYC
                                </Button>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* KYC documents */}
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>KYC Documents</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {documents.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">No documents uploaded.</p>
                        ) : (
                            <ul className="space-y-2">
                                {documents.map((d) => (
                                    <li key={d.id} className="flex flex-wrap items-center justify-between gap-2 rounded-md border p-3">
                                        <div>
                                            <div className="font-medium">{d.typeLabel}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {d.originalName} · {formatDateTime(d.uploadedAt)}
                                            </div>
                                            {d.rejectionReason && <div className="text-xs text-destructive">{d.rejectionReason}</div>}
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <StatusBadge status={d.status} />
                                            <Button variant="ghost" size="icon" asChild>
                                                <a href={`/admin/kyc-documents/${d.id}/download`}>
                                                    <Download className="h-4 w-4" />
                                                </a>
                                            </Button>
                                            {can.verifyKyc && d.status !== 'verified' && (
                                                <Button variant="ghost" size="icon" onClick={() => post(`/admin/kyc-documents/${d.id}/approve`)}>
                                                    <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                                </Button>
                                            )}
                                            {can.verifyKyc && d.status !== 'rejected' && (
                                                <Button variant="ghost" size="icon" onClick={() => rejectDoc(d.id)}>
                                                    <XCircle className="h-4 w-4 text-destructive" />
                                                </Button>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}

                        <h3 className="mt-6 mb-2 text-sm font-medium">Memberships</h3>
                        {memberships.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Not enrolled in any chitty.</p>
                        ) : (
                            <ul className="space-y-1 text-sm">
                                {memberships.map((m, i) => (
                                    <li key={i} className="flex justify-between border-b border-border/40 py-1">
                                        <span>{m.chittyName} ({m.chittyCode}) · #{m.ticketNumber}</span>
                                        <StatusBadge status={m.status} />
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

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex justify-between">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-medium">{value}</span>
        </div>
    );
}

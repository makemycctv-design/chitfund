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
import { Download, Trash2 } from 'lucide-react';

interface Profile {
    registrationStatus: string;
    kycStatus: string;
    kycLevel: number;
    dateOfBirth: string | null;
    gender: string | null;
    occupation: string | null;
    addressLine1: string | null;
    addressLine2: string | null;
    city: string | null;
    state: string | null;
    pincode: string | null;
    rejectionReason: string | null;
}
interface Doc { id: string; type: string; typeLabel: string; originalName: string; status: string; rejectionReason: string | null; uploadedAt: string | null }
interface Bank { id: string; accountHolderName: string; last4: string | null; ifsc: string; bankName: string; isPrimary: boolean; isVerified: boolean }
interface Props {
    profile: Profile;
    documents: Doc[];
    bankAccounts: Bank[];
    documentTypes: SelectOption[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Profile & KYC', href: '/portal/profile' }];

export default function PortalProfile({ profile, documents, bankAccounts, documentTypes }: Props) {
    const profileForm = useForm({
        date_of_birth: profile.dateOfBirth ?? '',
        gender: profile.gender ?? '',
        occupation: profile.occupation ?? '',
        address_line1: profile.addressLine1 ?? '',
        address_line2: profile.addressLine2 ?? '',
        city: profile.city ?? '',
        state: profile.state ?? '',
        pincode: profile.pincode ?? '',
    });

    const kycForm = useForm<{ type: string; file: File | null }>({ type: documentTypes[0]?.value as string, file: null });
    const bankForm = useForm({ account_holder_name: '', account_number: '', ifsc: '', bank_name: '', branch_name: '', is_primary: false });

    const saveProfile = (e: React.FormEvent) => {
        e.preventDefault();
        profileForm.patch('/portal/profile', { preserveScroll: true });
    };
    const uploadDoc = (e: React.FormEvent) => {
        e.preventDefault();
        kycForm.post('/portal/kyc-documents', { forceFormData: true, preserveScroll: true, onSuccess: () => kycForm.reset('file') });
    };
    const addBank = (e: React.FormEvent) => {
        e.preventDefault();
        bankForm.post('/portal/bank-accounts', { preserveScroll: true, onSuccess: () => bankForm.reset() });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile & KYC" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center gap-3">
                    <span className="text-sm text-muted-foreground">Registration:</span>
                    <StatusBadge status={profile.registrationStatus} />
                    <span className="text-sm text-muted-foreground">KYC:</span>
                    <StatusBadge status={profile.kycStatus} />
                    {profile.rejectionReason && <span className="text-xs text-destructive">({profile.rejectionReason})</span>}
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    {/* Profile details */}
                    <Card>
                        <CardHeader><CardTitle>Personal Details</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={saveProfile} className="grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-1.5">
                                    <Label>Date of Birth</Label>
                                    <Input type="date" value={profileForm.data.date_of_birth} onChange={(e) => profileForm.setData('date_of_birth', e.target.value)} />
                                </div>
                                <div className="grid gap-1.5">
                                    <Label>Gender</Label>
                                    <select value={profileForm.data.gender} onChange={(e) => profileForm.setData('gender', e.target.value)} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                        <option value="">—</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div className="grid gap-1.5 sm:col-span-2">
                                    <Label>Occupation</Label>
                                    <Input value={profileForm.data.occupation} onChange={(e) => profileForm.setData('occupation', e.target.value)} />
                                </div>
                                <div className="grid gap-1.5 sm:col-span-2">
                                    <Label>Address</Label>
                                    <Input value={profileForm.data.address_line1} onChange={(e) => profileForm.setData('address_line1', e.target.value)} placeholder="Line 1" />
                                </div>
                                <div className="grid gap-1.5">
                                    <Label>City</Label>
                                    <Input value={profileForm.data.city} onChange={(e) => profileForm.setData('city', e.target.value)} />
                                </div>
                                <div className="grid gap-1.5">
                                    <Label>Pincode</Label>
                                    <Input value={profileForm.data.pincode} onChange={(e) => profileForm.setData('pincode', e.target.value)} />
                                </div>
                                <div className="sm:col-span-2">
                                    <Button type="submit" disabled={profileForm.processing}>Save Details</Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* KYC docs */}
                    <Card>
                        <CardHeader><CardTitle>KYC Documents</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={uploadDoc} className="mb-4 flex flex-wrap items-end gap-2">
                                <div className="grid gap-1.5">
                                    <Label>Type</Label>
                                    <select value={kycForm.data.type} onChange={(e) => kycForm.setData('type', e.target.value)} className="h-9 rounded-md border border-input bg-background px-3 text-sm">
                                        {documentTypes.map((d) => (<option key={d.value} value={d.value}>{d.label}</option>))}
                                    </select>
                                </div>
                                <div className="grid gap-1.5">
                                    <Label>File (PDF/JPG/PNG)</Label>
                                    <Input type="file" accept=".pdf,.jpg,.jpeg,.png" onChange={(e) => kycForm.setData('file', e.target.files?.[0] ?? null)} />
                                </div>
                                <Button type="submit" disabled={kycForm.processing || !kycForm.data.file}>Upload</Button>
                            </form>
                            {kycForm.errors.file && <p className="mb-2 text-xs text-destructive">{kycForm.errors.file}</p>}

                            {documents.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No documents uploaded yet.</p>
                            ) : (
                                <ul className="space-y-2">
                                    {documents.map((d) => (
                                        <li key={d.id} className="flex items-center justify-between rounded-md border p-2 text-sm">
                                            <div>
                                                <div className="font-medium">{d.typeLabel}</div>
                                                <div className="text-xs text-muted-foreground">{formatDateTime(d.uploadedAt)}</div>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <StatusBadge status={d.status} />
                                                <a href={`/portal/kyc-documents/${d.id}/download`}><Download className="h-4 w-4" /></a>
                                                {d.status !== 'verified' && (
                                                    <button onClick={() => router.delete(`/portal/kyc-documents/${d.id}`, { preserveScroll: true })}>
                                                        <Trash2 className="h-4 w-4 text-destructive" />
                                                    </button>
                                                )}
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Bank accounts */}
                <Card>
                    <CardHeader><CardTitle>Bank Accounts (for prize payouts)</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={addBank} className="mb-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                            <Input placeholder="Account holder" value={bankForm.data.account_holder_name} onChange={(e) => bankForm.setData('account_holder_name', e.target.value)} />
                            <Input placeholder="Account number" value={bankForm.data.account_number} onChange={(e) => bankForm.setData('account_number', e.target.value)} />
                            <Input placeholder="IFSC" value={bankForm.data.ifsc} onChange={(e) => bankForm.setData('ifsc', e.target.value)} />
                            <Input placeholder="Bank name" value={bankForm.data.bank_name} onChange={(e) => bankForm.setData('bank_name', e.target.value)} />
                            <Button type="submit" disabled={bankForm.processing}>Add</Button>
                        </form>
                        {(bankForm.errors.account_number || bankForm.errors.ifsc) && (
                            <p className="mb-2 text-xs text-destructive">{bankForm.errors.account_number ?? bankForm.errors.ifsc}</p>
                        )}

                        {bankAccounts.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No bank accounts added.</p>
                        ) : (
                            <ul className="space-y-2">
                                {bankAccounts.map((b) => (
                                    <li key={b.id} className="flex items-center justify-between rounded-md border p-2 text-sm">
                                        <div>
                                            <span className="font-medium">{b.bankName}</span> ····{b.last4} · {b.ifsc}
                                            <div className="text-xs text-muted-foreground">{b.accountHolderName}</div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {b.isPrimary ? <StatusBadge status="approved" label="Primary" /> : (
                                                <Button variant="ghost" size="sm" onClick={() => router.post(`/portal/bank-accounts/${b.id}/primary`, {}, { preserveScroll: true })}>
                                                    Make primary
                                                </Button>
                                            )}
                                            <button onClick={() => router.delete(`/portal/bank-accounts/${b.id}`, { preserveScroll: true })}>
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </button>
                                        </div>
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

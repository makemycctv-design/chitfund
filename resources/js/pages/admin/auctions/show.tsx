import { StatCard } from '@/components/stat-card';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatCountdown, useAuctionState, type AuctionState } from '@/hooks/use-auction-state';
import { formatCurrency, formatDateTime } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Timer, Trophy, Users } from 'lucide-react';

interface Auction {
    id: string;
    chitty: string | null;
    chittyName: string | null;
    periodNo: number;
    methodLabel: string;
    foremanCommissionPercent: number;
}
interface Participant {
    ticketNumber: number | null;
    customerName: string | null;
    isEligible: boolean;
    ineligibleReason: string | null;
    isPresent: boolean;
}
interface Props {
    auction: Auction;
    state: AuctionState;
    participants: Participant[];
    can: { start: boolean; pause: boolean; resume: boolean; close: boolean; cancel: boolean };
}

export default function AuctionRoom({ auction, state: initial, participants, can }: Props) {
    const { state, remaining, refresh } = useAuctionState(`/admin/auctions/${auction.id}/state`, initial);
    const [duration, setDuration] = useState(5);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Auctions', href: '/admin/auctions' },
        { title: `${auction.chitty} #${auction.periodNo}`, href: `/admin/auctions/${auction.id}` },
    ];

    const act = (verb: string, data: Record<string, string | number> = {}) =>
        router.post(`/admin/auctions/${auction.id}/${verb}`, data, { preserveScroll: true, onSuccess: refresh });

    const cancel = () => {
        const reason = prompt('Reason for cancelling this auction?');
        if (reason) act('cancel', { reason });
    };

    const isLive = state.status === 'live';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Auction ${auction.chitty}`} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">{auction.chittyName} · Period #{auction.periodNo}</h1>
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            {auction.methodLabel} <StatusBadge status={state.status} />
                            {state.extendedCount > 0 && <span className="text-xs text-amber-600">extended ×{state.extendedCount}</span>}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {can.start && state.status === 'scheduled' && (
                            <div className="flex items-center gap-1">
                                <Input type="number" min={1} value={duration} onChange={(e) => setDuration(Number(e.target.value))} className="w-20" />
                                <span className="text-xs text-muted-foreground">min</span>
                                <Button onClick={() => act('start', { duration_minutes: duration })}>Start</Button>
                            </div>
                        )}
                        {can.pause && isLive && <Button variant="secondary" onClick={() => act('pause')}>Pause</Button>}
                        {can.resume && state.status === 'paused' && <Button variant="secondary" onClick={() => act('resume')}>Resume</Button>}
                        {can.close && (isLive || state.status === 'paused') && <Button onClick={() => act('finalize')}>Finalize</Button>}
                        {can.cancel && !['closed', 'cancelled'].includes(state.status) && (
                            <Button variant="outline" onClick={cancel}>Cancel</Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Time Remaining"
                        value={isLive ? formatCountdown(remaining) : '—'}
                        icon={Timer}
                        accent={isLive && remaining < 30 ? 'red' : 'blue'}
                    />
                    <StatCard
                        title="Best Bid (discount)"
                        value={state.bestBid ? formatCurrency(state.bestBid.amount) : '—'}
                        hint={state.bestBid?.ticketNumber ? `Ticket #${state.bestBid.ticketNumber}` : undefined}
                        icon={Trophy}
                        accent="emerald"
                    />
                    <StatCard title="Present / Eligible" value={`${state.presentCount}/${state.eligibleCount}`} icon={Users} />
                    <StatCard title="Chit Value" value={formatCurrency(state.chitValue)} />
                </div>

                {state.result && (
                    <Card>
                        <CardHeader><CardTitle>Result</CardTitle></CardHeader>
                        <CardContent className="grid gap-2 sm:grid-cols-4 text-sm">
                            <Field label="Discount" value={formatCurrency(state.result.discountAmount)} />
                            <Field label="Prize" value={formatCurrency(state.result.prizeAmount)} />
                            <Field label="Foreman Commission" value={formatCurrency(state.result.foremanCommission)} />
                            <Field label="Dividend / Member" value={formatCurrency(state.result.dividendPerMember)} />
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>Live Bids</CardTitle></CardHeader>
                        <CardContent>
                            {state.recentBids.length === 0 ? (
                                <p className="py-6 text-center text-sm text-muted-foreground">No bids yet.</p>
                            ) : (
                                <ul className="space-y-1 text-sm">
                                    {state.recentBids.map((b) => (
                                        <li key={b.id} className="flex justify-between border-b border-border/40 py-1">
                                            <span>Ticket #{b.ticketNumber}</span>
                                            <span className="font-medium">{formatCurrency(b.amount)}</span>
                                            <span className="text-xs text-muted-foreground">{formatDateTime(b.placedAt)}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Participants</CardTitle></CardHeader>
                        <CardContent>
                            <ul className="space-y-1 text-sm">
                                {participants.map((p, i) => (
                                    <li key={i} className="flex items-center justify-between border-b border-border/40 py-1">
                                        <span>#{p.ticketNumber} · {p.customerName}</span>
                                        {p.isEligible ? (
                                            <StatusBadge status={p.isPresent ? 'active' : 'pending'} label={p.isPresent ? 'Present' : 'Away'} />
                                        ) : (
                                            <span className="text-xs text-destructive" title={p.ineligibleReason ?? ''}>Ineligible</span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

function Field({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <div className="text-xs text-muted-foreground">{label}</div>
            <div className="font-semibold">{value}</div>
        </div>
    );
}

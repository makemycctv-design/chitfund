import { StatCard } from '@/components/stat-card';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatCountdown, useAuctionState, type AuctionState } from '@/hooks/use-auction-state';
import { formatCurrency } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Timer, Trophy } from 'lucide-react';
import { useState } from 'react';

interface Auction { id: string; chitty: string | null; chittyName: string | null; periodNo: number }
interface Me { ticketNumber: number | null; isEligible: boolean; ineligibleReason: string | null }
interface Props { auction: Auction; state: AuctionState; me: Me }

// Small ULID-ish client id for bid idempotency.
function randomKey(): string {
    return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
}

export default function PortalAuctionRoom({ auction, state: initial, me }: Props) {
    const { state, remaining, refresh } = useAuctionState(`/portal/auctions/${auction.id}/state`, initial);
    const [amount, setAmount] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Auctions', href: '/portal/auctions' },
        { title: `${auction.chitty} #${auction.periodNo}`, href: `/portal/auctions/${auction.id}` },
    ];

    const isLive = state.status === 'live';
    const suggested = state.bestBid ? state.bestBid.amount + state.bidIncrement : state.minBid + state.bidIncrement;

    const placeBid = (value: number | string) => {
        setSubmitting(true);
        router.post(
            `/portal/auctions/${auction.id}/bid`,
            { amount: String(value), idempotency_key: randomKey() },
            {
                preserveScroll: true,
                onSuccess: () => { setAmount(''); refresh(); },
                onFinish: () => setSubmitting(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Auction ${auction.chitty}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">{auction.chittyName}</h1>
                        <div className="text-sm text-muted-foreground">Period #{auction.periodNo} · Your ticket #{me.ticketNumber}</div>
                    </div>
                    <StatusBadge status={state.status} />
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard title="Time Remaining" value={isLive ? formatCountdown(remaining) : '—'} icon={Timer} accent={isLive && remaining < 30 ? 'red' : 'blue'} />
                    <StatCard title="Best Discount" value={state.bestBid ? formatCurrency(state.bestBid.amount) : '—'} icon={Trophy} accent="emerald" />
                    <StatCard title="Max Discount" value={formatCurrency(state.maxBid)} />
                </div>

                {/* Bidding */}
                <Card>
                    <CardHeader><CardTitle>Place a Bid (discount amount)</CardTitle></CardHeader>
                    <CardContent>
                        {!me.isEligible ? (
                            <p className="text-sm text-destructive">{me.ineligibleReason ?? 'You are not eligible to bid.'}</p>
                        ) : !isLive ? (
                            <p className="text-sm text-muted-foreground">Bidding is not open right now.</p>
                        ) : (
                            <div className="flex flex-wrap items-end gap-2">
                                <Button onClick={() => placeBid(suggested)} disabled={submitting || suggested > state.maxBid}>
                                    Quick bid {formatCurrency(suggested)}
                                </Button>
                                <span className="text-sm text-muted-foreground">or</span>
                                <Input
                                    type="number"
                                    step="0.01"
                                    placeholder="Custom amount"
                                    value={amount}
                                    onChange={(e) => setAmount(e.target.value)}
                                    className="w-40"
                                />
                                <Button variant="secondary" disabled={submitting || !amount} onClick={() => placeBid(amount)}>
                                    Bid
                                </Button>
                                <p className="w-full text-xs text-muted-foreground">
                                    Increment {formatCurrency(state.bidIncrement)} · Range {formatCurrency(state.minBid)}–{formatCurrency(state.maxBid)}
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {state.result && (
                    <Card>
                        <CardHeader><CardTitle>Result</CardTitle></CardHeader>
                        <CardContent className="text-sm">
                            <p>Winning discount: <b>{formatCurrency(state.result.discountAmount)}</b></p>
                            <p>Prize amount: <b>{formatCurrency(state.result.prizeAmount)}</b></p>
                            <p>Your dividend this month: <b>{formatCurrency(state.result.dividendPerMember)}</b></p>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader><CardTitle>Recent Bids</CardTitle></CardHeader>
                    <CardContent>
                        {state.recentBids.length === 0 ? (
                            <p className="py-6 text-center text-sm text-muted-foreground">No bids yet — be the first!</p>
                        ) : (
                            <ul className="space-y-1 text-sm">
                                {state.recentBids.map((b) => (
                                    <li key={b.id} className="flex justify-between border-b border-border/40 py-1">
                                        <span>Ticket #{b.ticketNumber}{b.ticketNumber === me.ticketNumber ? ' (you)' : ''}</span>
                                        <span className="font-medium">{formatCurrency(b.amount)}</span>
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

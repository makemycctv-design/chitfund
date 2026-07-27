import { ApiError } from '@/api/client';
import { api } from '@/api/endpoints';
import type { AuctionState } from '@/api/types';
import { Button, Card, ErrorText, Heading, Loading, Muted, StatusPill } from '@/components/ui';
import { formatCountdown, useAuctionState } from '@/hooks/useAuctionState';
import { colors, formatCurrency, spacing } from '@/theme';
import { useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { ScrollView, Text, TextInput, View } from 'react-native';

function randomKey() {
    return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
}

export default function AuctionRoom() {
    const { id } = useLocalSearchParams<{ id: string }>();
    const [initial, setInitial] = useState<AuctionState | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!id) return;
        api.auction(id).then(setInitial).catch(() => setInitial(null)).finally(() => setLoading(false));
    }, [id]);

    if (loading) return <Loading />;
    if (!initial || !id) return <View style={{ padding: spacing.xl }}><Muted>Auction not available.</Muted></View>;

    return <Room id={id} initial={initial} />;
}

function Room({ id, initial }: { id: string; initial: AuctionState }) {
    const { state, remaining, refresh } = useAuctionState(id, initial);
    const [amount, setAmount] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const isLive = state.status === 'live';
    const suggested = (state.bestBid ? state.bestBid.amount : state.minBid) + state.bidIncrement;

    const bid = async (value: number) => {
        setError(null);
        setSubmitting(true);
        try {
            await api.placeBid(id, value, randomKey());
            setAmount('');
            await refresh();
        } catch (e) {
            setError(e instanceof ApiError ? e.message : 'Bid failed.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <ScrollView contentContainerStyle={{ padding: spacing.lg, gap: spacing.md }}>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                <Heading>Live Auction</Heading>
                <StatusPill status={state.status} />
            </View>

            <View style={{ flexDirection: 'row', gap: spacing.md }}>
                <Card style={{ flex: 1, alignItems: 'center' }}>
                    <Muted>Time left</Muted>
                    <Text style={{ fontSize: 26, fontWeight: '800', color: isLive && remaining < 30 ? colors.danger : colors.primary }}>
                        {isLive ? formatCountdown(remaining) : '—'}
                    </Text>
                </Card>
                <Card style={{ flex: 1, alignItems: 'center' }}>
                    <Muted>Best discount</Muted>
                    <Text style={{ fontSize: 22, fontWeight: '800', color: colors.success }}>
                        {state.bestBid ? formatCurrency(state.bestBid.amount) : '—'}
                    </Text>
                </Card>
            </View>

            {state.result ? (
                <Card>
                    <Heading>Result</Heading>
                    <Muted>Winning discount: {formatCurrency(state.result.discountAmount)}</Muted>
                    <Muted>Prize: {formatCurrency(state.result.prizeAmount)}</Muted>
                    <Muted>Your dividend: {formatCurrency(state.result.dividendPerMember)}</Muted>
                </Card>
            ) : isLive ? (
                <Card>
                    <Heading>Place a bid (discount)</Heading>
                    <Muted>Range {formatCurrency(state.minBid)}–{formatCurrency(state.maxBid)} · increment {formatCurrency(state.bidIncrement)}</Muted>
                    <Button title={`Quick bid ${formatCurrency(suggested)}`} disabled={suggested > state.maxBid || submitting} onPress={() => bid(suggested)} />
                    <TextInput
                        keyboardType="numeric"
                        placeholder="Custom amount"
                        placeholderTextColor={colors.muted}
                        value={amount}
                        onChangeText={setAmount}
                        style={{ borderWidth: 1, borderColor: colors.border, borderRadius: 10, padding: spacing.md, color: colors.text }}
                    />
                    <Button title="Bid" variant="outline" disabled={!amount || submitting} onPress={() => bid(Number(amount))} />
                    {error ? <ErrorText message={error} /> : null}
                </Card>
            ) : (
                <Muted>Bidding is not open right now.</Muted>
            )}

            <Heading>Recent bids</Heading>
            {state.recentBids.length === 0 ? (
                <Muted>No bids yet.</Muted>
            ) : (
                state.recentBids.map((b) => (
                    <Card key={b.id}>
                        <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                            <Muted>Ticket #{b.ticketNumber ?? '—'}</Muted>
                            <Text style={{ fontWeight: '700', color: colors.text }}>{formatCurrency(b.amount)}</Text>
                        </View>
                    </Card>
                ))
            )}
        </ScrollView>
    );
}

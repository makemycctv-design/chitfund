import { api } from '@/api/endpoints';
import { ApiError } from '@/api/client';
import type { ChittyDetail } from '@/api/types';
import { Button, Card, Heading, Loading, Muted, StatusPill } from '@/components/ui';
import { colors, formatCurrency, formatDate, spacing } from '@/theme';
import { useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, ScrollView, Text, View } from 'react-native';

export default function ChittyDetailScreen() {
    const { id } = useLocalSearchParams<{ id: string }>();
    const [chitty, setChitty] = useState<ChittyDetail | null>(null);
    const [loading, setLoading] = useState(true);
    const [paying, setPaying] = useState<string | null>(null);

    const load = () => {
        if (!id) return;
        api.chitty(id).then(setChitty).catch(() => setChitty(null)).finally(() => setLoading(false));
    };
    useEffect(load, [id]);

    const pay = async (installmentId: string) => {
        setPaying(installmentId);
        try {
            const res = await api.initiatePayment(installmentId, 'upi');
            Alert.alert('Payment initiated', `Reference ${res.reference}. Complete it in the gateway; your ledger updates once confirmed.`);
        } catch (e) {
            Alert.alert('Payment', e instanceof ApiError ? e.message : 'Could not start payment.');
        } finally {
            setPaying(null);
        }
    };

    if (loading) return <Loading />;
    if (!chitty) return <View style={{ padding: spacing.xl }}><Muted>Chitty not found.</Muted></View>;

    return (
        <ScrollView contentContainerStyle={{ padding: spacing.lg, gap: spacing.md }}>
            <Card>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                    <Heading>{chitty.name}</Heading>
                    <StatusPill status={chitty.status} />
                </View>
                <Muted>{chitty.code} · Ticket #{chitty.ticketNumber ?? '—'}</Muted>
                <Text style={{ fontSize: 18, fontWeight: '800', color: colors.primary }}>{formatCurrency(chitty.installmentAmount)} /installment</Text>
                <Muted>{chitty.durationMonths} months</Muted>
            </Card>

            <Heading>Installment ledger</Heading>
            {chitty.installments.length === 0 ? (
                <Muted>The schedule has not been generated yet.</Muted>
            ) : (
                chitty.installments.map((i) => {
                    const payable = ['pending', 'partial', 'overdue'].includes(i.status);
                    return (
                        <Card key={i.id}>
                            <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                                <View>
                                    <Text style={{ fontWeight: '700', color: colors.text }}>Period #{i.periodNo}</Text>
                                    <Muted>Due {formatDate(i.dueDate)}</Muted>
                                </View>
                                <StatusPill status={i.status} />
                            </View>
                            <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                                <Text style={{ fontWeight: '700', color: colors.text }}>{formatCurrency(i.outstanding || i.amountDue)}</Text>
                                {payable ? (
                                    <View style={{ width: 140 }}>
                                        <Button title={`Pay`} loading={paying === i.id} onPress={() => pay(i.id)} />
                                    </View>
                                ) : null}
                            </View>
                        </Card>
                    );
                })
            )}
        </ScrollView>
    );
}

import { api } from '@/api/endpoints';
import type { ChittyListItem, Installment } from '@/api/types';
import { useAuth } from '@/auth/AuthContext';
import { Button, Card, ErrorText, Heading, Loading, Muted, StatusPill } from '@/components/ui';
import { colors, formatCurrency, formatDate, spacing } from '@/theme';
import { useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { RefreshControl, ScrollView, Text, View } from 'react-native';

export default function Home() {
    const { user } = useAuth();
    const router = useRouter();
    const [chitties, setChitties] = useState<ChittyListItem[]>([]);
    const [outstanding, setOutstanding] = useState<Installment[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const load = useCallback(async () => {
        setError(null);
        try {
            const [c, i] = await Promise.all([api.chitties(), api.installments(true)]);
            setChitties(c);
            setOutstanding(i.data);
        } catch {
            setError('Could not load your dashboard.');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, []);

    useFocusEffect(useCallback(() => { load(); }, [load]));

    if (loading) return <Loading />;

    const nextDue = [...outstanding].sort((a, b) => (a.dueDate ?? '').localeCompare(b.dueDate ?? ''))[0];
    const overdue = outstanding.filter((i) => i.dueDate && new Date(i.dueDate) < new Date());
    const overdueTotal = overdue.reduce((s, i) => s + i.outstanding, 0);

    return (
        <ScrollView
            contentContainerStyle={{ padding: spacing.lg, gap: spacing.md }}
            refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(); }} />}
        >
            <Heading>Hi, {user?.name?.split(' ')[0] ?? 'there'} 👋</Heading>
            {error ? <ErrorText message={error} /> : null}

            <View style={{ flexDirection: 'row', gap: spacing.md }}>
                <Card style={{ flex: 1 }}>
                    <Muted>Next due</Muted>
                    <Text style={{ fontSize: 20, fontWeight: '800', color: colors.primary }}>
                        {nextDue ? formatCurrency(nextDue.outstanding) : '—'}
                    </Text>
                    <Muted>{nextDue ? formatDate(nextDue.dueDate) : 'All settled'}</Muted>
                </Card>
                <Card style={{ flex: 1 }}>
                    <Muted>Overdue</Muted>
                    <Text style={{ fontSize: 20, fontWeight: '800', color: overdueTotal > 0 ? colors.danger : colors.success }}>
                        {formatCurrency(overdueTotal)}
                    </Text>
                    <Muted>{overdue.length} installment(s)</Muted>
                </Card>
            </View>

            <Card>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                    <Heading>My chitties</Heading>
                    <Text onPress={() => router.push('/chitties' as never)} style={{ color: colors.primary, fontWeight: '700' }}>All</Text>
                </View>
                {chitties.length === 0 ? (
                    <Muted>You are not enrolled in any chitties yet.</Muted>
                ) : (
                    chitties.slice(0, 4).map((c) => (
                        <View
                            key={c.id}
                            style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: spacing.sm, borderBottomWidth: 1, borderBottomColor: colors.border }}
                        >
                            <View>
                                <Text style={{ fontWeight: '600', color: colors.text }} onPress={() => c.id && router.push(`/chitties/${c.id}` as never)}>
                                    {c.name}
                                </Text>
                                <Muted>{c.code} · #{c.ticketNumber ?? '—'}</Muted>
                            </View>
                            <StatusPill status={c.status} />
                        </View>
                    ))
                )}
            </Card>

            <Button title="View payments" variant="outline" onPress={() => router.push('/payments' as never)} />
        </ScrollView>
    );
}

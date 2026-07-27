import { api } from '@/api/endpoints';
import type { PaymentTxn } from '@/api/types';
import { Card, Heading, Loading, Muted, StatusPill } from '@/components/ui';
import { colors, formatCurrency, formatDate, spacing } from '@/theme';
import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { FlatList, Text, View } from 'react-native';

export default function Payments() {
    const [items, setItems] = useState<PaymentTxn[]>([]);
    const [loading, setLoading] = useState(true);

    useFocusEffect(
        useCallback(() => {
            api.payments().then((p) => setItems(p.data)).catch(() => setItems([])).finally(() => setLoading(false));
        }, []),
    );

    if (loading) return <Loading />;

    return (
        <FlatList
            contentContainerStyle={{ padding: spacing.lg, gap: spacing.md }}
            data={items}
            keyExtractor={(t) => t.id}
            ListHeaderComponent={<Heading>Payment history</Heading>}
            ListEmptyComponent={<Muted>No payments yet.</Muted>}
            renderItem={({ item }) => (
                <Card>
                    <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                        <Text style={{ fontWeight: '700', color: colors.text }}>{formatCurrency(item.amount)}</Text>
                        <StatusPill status={item.status} />
                    </View>
                    <Muted>{item.reference} · {item.method ?? ''}</Muted>
                    <Muted>{formatDate(item.createdAt)}{item.receiptNumber ? ` · Receipt ${item.receiptNumber}` : ''}</Muted>
                </Card>
            )}
        />
    );
}

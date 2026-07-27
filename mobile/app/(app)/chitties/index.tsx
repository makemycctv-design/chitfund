import { api } from '@/api/endpoints';
import type { ChittyListItem } from '@/api/types';
import { Card, Heading, Loading, Muted, StatusPill } from '@/components/ui';
import { colors, formatCurrency, spacing } from '@/theme';
import { useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { FlatList, Pressable, Text, View } from 'react-native';

export default function Chitties() {
    const router = useRouter();
    const [items, setItems] = useState<ChittyListItem[]>([]);
    const [loading, setLoading] = useState(true);

    useFocusEffect(
        useCallback(() => {
            api.chitties().then(setItems).catch(() => setItems([])).finally(() => setLoading(false));
        }, []),
    );

    if (loading) return <Loading />;

    return (
        <FlatList
            contentContainerStyle={{ padding: spacing.lg, gap: spacing.md }}
            data={items}
            keyExtractor={(c) => c.id ?? Math.random().toString()}
            ListEmptyComponent={<Muted>You are not enrolled in any chitties yet.</Muted>}
            renderItem={({ item }) => (
                <Pressable onPress={() => item.id && router.push(`/chitties/${item.id}` as never)}>
                    <Card>
                        <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                            <Heading>{item.name}</Heading>
                            <StatusPill status={item.status} />
                        </View>
                        <Muted>{item.code} · Ticket #{item.ticketNumber ?? '—'}</Muted>
                        <Text style={{ fontSize: 18, fontWeight: '800', color: colors.primary }}>
                            {formatCurrency(item.installmentAmount)}
                            <Text style={{ fontSize: 12, color: colors.muted, fontWeight: '400' }}> /installment</Text>
                        </Text>
                        {item.isPrized ? <Muted>🏆 Prize won</Muted> : null}
                    </Card>
                </Pressable>
            )}
        />
    );
}

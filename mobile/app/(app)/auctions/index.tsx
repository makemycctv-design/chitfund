import { api } from '@/api/endpoints';
import type { AuctionListItem } from '@/api/types';
import { Card, Heading, Loading, Muted, StatusPill } from '@/components/ui';
import { formatDate, spacing } from '@/theme';
import { useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { FlatList, Pressable, View } from 'react-native';

export default function Auctions() {
    const router = useRouter();
    const [items, setItems] = useState<AuctionListItem[]>([]);
    const [loading, setLoading] = useState(true);

    useFocusEffect(
        useCallback(() => {
            api.auctions().then(setItems).catch(() => setItems([])).finally(() => setLoading(false));
        }, []),
    );

    if (loading) return <Loading />;

    return (
        <FlatList
            contentContainerStyle={{ padding: spacing.lg, gap: spacing.md }}
            data={items}
            keyExtractor={(a) => a.id}
            ListHeaderComponent={<Heading>Auctions</Heading>}
            ListEmptyComponent={<Muted>No auctions for your chitties yet.</Muted>}
            renderItem={({ item }) => (
                <Pressable onPress={() => router.push(`/auctions/${item.id}` as never)}>
                    <Card>
                        <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                            <Heading>{item.chitty}</Heading>
                            <StatusPill status={item.status} />
                        </View>
                        <Muted>Period #{item.periodNo}</Muted>
                        {item.endsAt ? <Muted>Ends {formatDate(item.endsAt)}</Muted> : null}
                    </Card>
                </Pressable>
            )}
        />
    );
}

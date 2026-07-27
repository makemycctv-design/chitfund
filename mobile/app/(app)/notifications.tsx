import { api } from '@/api/endpoints';
import type { NotificationItem } from '@/api/types';
import { Button, Card, Heading, Loading, Muted } from '@/components/ui';
import { colors, formatDate, spacing } from '@/theme';
import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { FlatList, Text, View } from 'react-native';

export default function NotificationsScreen() {
    const [items, setItems] = useState<NotificationItem[]>([]);
    const [loading, setLoading] = useState(true);

    const load = useCallback(() => {
        api.notifications().then((r) => setItems(r.items)).catch(() => setItems([])).finally(() => setLoading(false));
    }, []);

    useFocusEffect(useCallback(() => { load(); }, [load]));

    const markAll = async () => {
        await api.markAllRead().catch(() => undefined);
        load();
    };

    if (loading) return <Loading />;

    return (
        <FlatList
            contentContainerStyle={{ padding: spacing.lg, gap: spacing.md }}
            data={items}
            keyExtractor={(n) => n.id}
            ListHeaderComponent={
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: spacing.sm }}>
                    <Heading>Notifications</Heading>
                    {items.some((n) => !n.read) ? <View style={{ width: 120 }}><Button title="Mark all read" variant="outline" onPress={markAll} /></View> : null}
                </View>
            }
            ListEmptyComponent={<Muted>No notifications.</Muted>}
            renderItem={({ item }) => (
                <Card style={item.read ? { opacity: 0.65 } : { borderColor: colors.primary }}>
                    <Text style={{ fontWeight: '700', color: colors.text }}>{item.title}</Text>
                    <Muted>{item.message}</Muted>
                    <Muted>{formatDate(item.createdAt)}</Muted>
                </Card>
            )}
        />
    );
}

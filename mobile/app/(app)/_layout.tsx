import { useAuth } from '@/auth/AuthContext';
import { authenticateBiometric, biometricsAvailable } from '@/auth/biometric';
import { Button, Loading } from '@/components/ui';
import { colors, spacing } from '@/theme';
import { Ionicons } from '@expo/vector-icons';
import { Redirect, Tabs } from 'expo-router';
import { useEffect, useState } from 'react';
import { ColorValue, Text, View } from 'react-native';

export default function AppLayout() {
    const { user, ready } = useAuth();
    const [locked, setLocked] = useState(false);
    const [checked, setChecked] = useState(false);

    // Require a biometric unlock on entry when the device supports it.
    useEffect(() => {
        (async () => {
            if (await biometricsAvailable()) {
                setLocked(true);
                const ok = await authenticateBiometric('Unlock ChittyFund');
                setLocked(!ok);
            }
            setChecked(true);
        })();
    }, []);

    if (!ready) return <Loading />;
    if (!user) return <Redirect href="/(auth)/login" />;
    if (!checked) return <Loading />;

    if (locked) {
        return (
            <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', gap: spacing.lg, padding: spacing.xl }}>
                <Ionicons name="lock-closed" size={48} color={colors.primary} />
                <Text style={{ color: colors.muted, textAlign: 'center' }}>Unlock to continue.</Text>
                <Button title="Unlock" onPress={async () => setLocked(!(await authenticateBiometric()))} />
            </View>
        );
    }

    const icon = (name: keyof typeof Ionicons.glyphMap) =>
        ({ color, size }: { color: ColorValue; size: number }) => <Ionicons name={name} color={color as string} size={size} />;

    return (
        <Tabs screenOptions={{ tabBarActiveTintColor: colors.primary, headerShown: true }}>
            <Tabs.Screen name="index" options={{ title: 'Home', tabBarIcon: icon('home-outline') }} />
            <Tabs.Screen name="chitties/index" options={{ title: 'Chitties', tabBarIcon: icon('wallet-outline') }} />
            <Tabs.Screen name="auctions/index" options={{ title: 'Auctions', tabBarIcon: icon('hammer-outline') }} />
            <Tabs.Screen name="notifications" options={{ title: 'Alerts', tabBarIcon: icon('notifications-outline') }} />
            <Tabs.Screen name="profile" options={{ title: 'Profile', tabBarIcon: icon('person-outline') }} />

            {/* Detail routes — navigable but hidden from the tab bar. */}
            <Tabs.Screen name="chitties/[id]" options={{ href: null, title: 'Chitty' }} />
            <Tabs.Screen name="auctions/[id]" options={{ href: null, title: 'Auction' }} />
            <Tabs.Screen name="payments" options={{ href: null, title: 'Payments' }} />
        </Tabs>
    );
}

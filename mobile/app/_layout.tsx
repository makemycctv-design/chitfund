import { AuthProvider } from '@/auth/AuthContext';
import { colors } from '@/theme';
import { Stack, useRouter } from 'expo-router';
import * as Notifications from 'expo-notifications';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';

/** Map a backend deep-link/web path to the closest mobile route. */
function mapDeepLink(url: string): string {
    if (url.includes('auction')) return '/auctions';
    if (url.includes('payment')) return '/payments';
    if (url.includes('chitt')) return '/chitties';
    if (url.includes('support')) return '/notifications';
    return '/notifications';
}

export default function RootLayout() {
    const router = useRouter();

    // Route the user when they tap a push notification.
    useEffect(() => {
        const sub = Notifications.addNotificationResponseReceivedListener((response) => {
            const url = response.notification.request.content.data?.url;
            if (typeof url === 'string') {
                router.push(mapDeepLink(url) as never);
            }
        });
        return () => sub.remove();
    }, [router]);

    return (
        <GestureHandlerRootView style={{ flex: 1 }}>
            <SafeAreaProvider>
                <AuthProvider>
                    <StatusBar style="dark" />
                    <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: colors.background } }} />
                </AuthProvider>
            </SafeAreaProvider>
        </GestureHandlerRootView>
    );
}

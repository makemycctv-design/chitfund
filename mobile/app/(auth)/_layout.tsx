import { useAuth } from '@/auth/AuthContext';
import { Redirect, Stack } from 'expo-router';

export default function AuthLayout() {
    const { user, ready } = useAuth();

    // Already signed in → go to the app.
    if (ready && user) return <Redirect href="/(app)" />;

    return <Stack screenOptions={{ headerShown: false }} />;
}

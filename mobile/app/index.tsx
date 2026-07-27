import { useAuth } from '@/auth/AuthContext';
import { Loading } from '@/components/ui';
import { Redirect } from 'expo-router';

/** Entry gate: waits for token restore, then routes to the app or to login. */
export default function Index() {
    const { ready, user } = useAuth();

    if (!ready) return <Loading />;

    return <Redirect href={user ? '/(app)' : '/(auth)/login'} />;
}

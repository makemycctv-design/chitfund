import { ApiError } from '@/api/client';
import { useAuth } from '@/auth/AuthContext';
import { Button, ErrorText, Field, Heading, Muted } from '@/components/ui';
import { colors, spacing } from '@/theme';
import { Link } from 'expo-router';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, Text, View } from 'react-native';

export default function Login() {
    const { login } = useAuth();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const submit = async () => {
        setError(null);
        setLoading(true);
        try {
            await login(email.trim(), password);
        } catch (e) {
            setError(e instanceof ApiError ? e.message : 'Login failed.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
            <ScrollView contentContainerStyle={{ flexGrow: 1, justifyContent: 'center', padding: spacing.xl, gap: spacing.lg }}>
                <View style={{ gap: spacing.xs }}>
                    <Text style={{ fontSize: 28, fontWeight: '800', color: colors.primary }}>ChittyFund</Text>
                    <Heading>Sign in</Heading>
                    <Muted>Access your chitties, payments and auctions.</Muted>
                </View>

                <Field label="Email" autoCapitalize="none" keyboardType="email-address" value={email} onChangeText={setEmail} placeholder="you@example.com" />
                <Field label="Password" secureTextEntry value={password} onChangeText={setPassword} placeholder="••••••••" />

                {error ? <ErrorText message={error} /> : null}

                <Button title="Sign in" onPress={submit} loading={loading} disabled={!email || !password} />

                <View style={{ flexDirection: 'row', justifyContent: 'center', gap: spacing.xs }}>
                    <Muted>New here?</Muted>
                    <Link href="/(auth)/register" style={{ color: colors.primary, fontWeight: '700' }}>
                        Create an account
                    </Link>
                </View>
            </ScrollView>
        </KeyboardAvoidingView>
    );
}

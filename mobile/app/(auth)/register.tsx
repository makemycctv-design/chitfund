import { ApiError } from '@/api/client';
import { useAuth } from '@/auth/AuthContext';
import { Button, ErrorText, Field, Heading, Muted } from '@/components/ui';
import { colors, spacing } from '@/theme';
import { Link } from 'expo-router';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, View } from 'react-native';

export default function Register() {
    const { register } = useAuth();
    const [form, setForm] = useState({ name: '', email: '', phone: '', password: '' });
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const set = (k: keyof typeof form) => (v: string) => setForm((f) => ({ ...f, [k]: v }));

    const submit = async () => {
        setError(null);
        setLoading(true);
        try {
            await register({ name: form.name.trim(), email: form.email.trim(), phone: form.phone.trim() || undefined, password: form.password });
        } catch (e) {
            setError(e instanceof ApiError ? e.message : 'Registration failed.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
            <ScrollView contentContainerStyle={{ flexGrow: 1, justifyContent: 'center', padding: spacing.xl, gap: spacing.lg }}>
                <Heading>Create your account</Heading>
                <Muted>Registration is reviewed by staff before you can enroll.</Muted>

                <Field label="Full name" value={form.name} onChangeText={set('name')} />
                <Field label="Email" autoCapitalize="none" keyboardType="email-address" value={form.email} onChangeText={set('email')} />
                <Field label="Phone (optional)" keyboardType="phone-pad" value={form.phone} onChangeText={set('phone')} />
                <Field label="Password" secureTextEntry value={form.password} onChangeText={set('password')} />

                {error ? <ErrorText message={error} /> : null}

                <Button title="Create account" onPress={submit} loading={loading} disabled={!form.name || !form.email || !form.password} />

                <View style={{ flexDirection: 'row', justifyContent: 'center', gap: spacing.xs }}>
                    <Muted>Have an account?</Muted>
                    <Link href="/(auth)/login" style={{ color: colors.primary, fontWeight: '700' }}>
                        Sign in
                    </Link>
                </View>
            </ScrollView>
        </KeyboardAvoidingView>
    );
}

import { colors, radius, spacing } from '@/theme';
import React from 'react';
import {
    ActivityIndicator,
    Pressable,
    StyleSheet,
    Text,
    TextInput,
    TextInputProps,
    View,
    ViewProps,
} from 'react-native';

export function Card({ style, children, ...rest }: ViewProps) {
    return (
        <View style={[styles.card, style]} {...rest}>
            {children}
        </View>
    );
}

export function Heading({ children }: { children: React.ReactNode }) {
    return <Text style={styles.heading}>{children}</Text>;
}

export function Muted({ children }: { children: React.ReactNode }) {
    return <Text style={styles.muted}>{children}</Text>;
}

export function Button({
    title,
    onPress,
    disabled,
    variant = 'primary',
    loading,
}: {
    title: string;
    onPress: () => void;
    disabled?: boolean;
    variant?: 'primary' | 'outline';
    loading?: boolean;
}) {
    const isOutline = variant === 'outline';
    return (
        <Pressable
            onPress={onPress}
            disabled={disabled || loading}
            style={({ pressed }) => [
                styles.button,
                isOutline ? styles.buttonOutline : styles.buttonPrimary,
                (disabled || loading) && { opacity: 0.5 },
                pressed && { opacity: 0.8 },
            ]}
        >
            {loading ? (
                <ActivityIndicator color={isOutline ? colors.primary : colors.primaryText} />
            ) : (
                <Text style={[styles.buttonText, isOutline && { color: colors.primary }]}>{title}</Text>
            )}
        </Pressable>
    );
}

export function Field({ label, error, ...rest }: TextInputProps & { label: string; error?: string }) {
    return (
        <View style={{ gap: spacing.xs }}>
            <Text style={styles.label}>{label}</Text>
            <TextInput style={styles.input} placeholderTextColor={colors.muted} {...rest} />
            {error ? <Text style={styles.error}>{error}</Text> : null}
        </View>
    );
}

export function StatusPill({ status }: { status: string | null | undefined }) {
    const map: Record<string, string> = {
        active: colors.success, approved: colors.success, verified: colors.success, paid: colors.success, success: colors.success, live: colors.success,
        pending: colors.warning, partial: colors.warning, submitted: colors.warning, scheduled: colors.warning,
        rejected: colors.danger, overdue: colors.danger, failed: colors.danger, cancelled: colors.danger, closed: colors.muted,
    };
    const color = status ? (map[status] ?? colors.muted) : colors.muted;
    return (
        <View style={[styles.pill, { backgroundColor: `${color}22` }]}>
            <Text style={[styles.pillText, { color }]}>{status ? status.replace(/_/g, ' ') : '—'}</Text>
        </View>
    );
}

export function Loading() {
    return (
        <View style={styles.center}>
            <ActivityIndicator size="large" color={colors.primary} />
        </View>
    );
}

export function ErrorText({ message }: { message: string }) {
    return <Text style={styles.error}>{message}</Text>;
}

const styles = StyleSheet.create({
    card: {
        backgroundColor: colors.card,
        borderRadius: radius.lg,
        padding: spacing.lg,
        borderWidth: 1,
        borderColor: colors.border,
        gap: spacing.sm,
    },
    heading: { fontSize: 18, fontWeight: '700', color: colors.text },
    muted: { color: colors.muted, fontSize: 13 },
    label: { fontSize: 13, color: colors.muted, fontWeight: '600' },
    input: {
        borderWidth: 1, borderColor: colors.border, borderRadius: radius.md,
        paddingHorizontal: spacing.md, paddingVertical: spacing.md, fontSize: 15, color: colors.text, backgroundColor: colors.card,
    },
    error: { color: colors.danger, fontSize: 12 },
    button: { borderRadius: radius.md, paddingVertical: spacing.md, alignItems: 'center', justifyContent: 'center' },
    buttonPrimary: { backgroundColor: colors.primary },
    buttonOutline: { borderWidth: 1, borderColor: colors.primary, backgroundColor: 'transparent' },
    buttonText: { color: colors.primaryText, fontWeight: '700', fontSize: 15 },
    pill: { alignSelf: 'flex-start', paddingHorizontal: spacing.sm, paddingVertical: 2, borderRadius: 999 },
    pillText: { fontSize: 11, fontWeight: '700', textTransform: 'capitalize' },
    center: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: spacing.xl },
});

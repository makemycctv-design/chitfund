import { useAuth } from '@/auth/AuthContext';
import { Button, Card, Heading, Muted, StatusPill } from '@/components/ui';
import { colors, spacing } from '@/theme';
import { ScrollView, Text, View } from 'react-native';

export default function Profile() {
    const { user, logout } = useAuth();
    const profile = user?.customerProfile;

    return (
        <ScrollView contentContainerStyle={{ padding: spacing.lg, gap: spacing.md }}>
            <Card>
                <Heading>{user?.name}</Heading>
                <Muted>{user?.email}</Muted>
                {user?.phone ? <Muted>{user.phone}</Muted> : null}
            </Card>

            <Card>
                <Text style={{ fontWeight: '700', color: colors.text }}>Account status</Text>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                    <Muted>Registration</Muted>
                    <StatusPill status={profile?.registrationStatus} />
                </View>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                    <Muted>KYC</Muted>
                    <StatusPill status={profile?.kycStatus} />
                </View>
            </Card>

            <Card>
                <Text style={{ fontWeight: '700', color: colors.text }}>Security</Text>
                <Muted>Your session token is stored in the device keychain. Biometric unlock protects the app on supported devices.</Muted>
            </Card>

            <Button title="Sign out" variant="outline" onPress={logout} />
        </ScrollView>
    );
}

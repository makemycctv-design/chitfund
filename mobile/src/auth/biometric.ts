import * as LocalAuthentication from 'expo-local-authentication';

/** Whether the device has enrolled biometrics (Face ID / fingerprint). */
export async function biometricsAvailable(): Promise<boolean> {
    const hasHardware = await LocalAuthentication.hasHardwareAsync();
    const enrolled = await LocalAuthentication.isEnrolledAsync();
    return hasHardware && enrolled;
}

/**
 * Prompt for biometric unlock. Returns true on success. Used to gate access to
 * the authenticated area when a token already exists on the device.
 */
export async function authenticateBiometric(reason = 'Unlock ChittyFund'): Promise<boolean> {
    const result = await LocalAuthentication.authenticateAsync({
        promptMessage: reason,
        disableDeviceFallback: false,
        cancelLabel: 'Use password',
    });
    return result.success;
}

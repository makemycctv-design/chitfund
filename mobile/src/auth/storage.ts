import * as SecureStore from 'expo-secure-store';

/**
 * Secure token storage backed by the device keychain/keystore (expo-secure-store).
 * The Sanctum token is never persisted in plain AsyncStorage.
 */
const TOKEN_KEY = 'chittyfund_token';

export async function saveToken(token: string): Promise<void> {
    await SecureStore.setItemAsync(TOKEN_KEY, token, {
        keychainAccessible: SecureStore.WHEN_UNLOCKED,
    });
}

export async function getToken(): Promise<string | null> {
    return SecureStore.getItemAsync(TOKEN_KEY);
}

export async function clearToken(): Promise<void> {
    await SecureStore.deleteItemAsync(TOKEN_KEY);
}

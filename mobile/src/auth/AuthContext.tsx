import { api } from '@/api/endpoints';
import { setAuthToken } from '@/api/client';
import type { AuthUser } from '@/api/types';
import { registerForPushToken, platformName } from '@/lib/push';
import { clearToken, getToken, saveToken } from './storage';
import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';

interface AuthContextValue {
    user: AuthUser | null;
    token: string | null;
    /** True once the initial token restore has completed. */
    ready: boolean;
    login: (email: string, password: string) => Promise<void>;
    register: (payload: { name: string; email: string; phone?: string; password: string }) => Promise<void>;
    logout: () => Promise<void>;
    refresh: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

const DEVICE_NAME = `expo-${platformName()}`;

export function AuthProvider({ children }: { children: React.ReactNode }) {
    const [user, setUser] = useState<AuthUser | null>(null);
    const [token, setToken] = useState<string | null>(null);
    const [ready, setReady] = useState(false);

    // Restore a persisted token on cold start.
    useEffect(() => {
        (async () => {
            const stored = await getToken();
            if (stored) {
                setAuthToken(stored);
                setToken(stored);
                try {
                    setUser(await api.me());
                } catch {
                    await clearToken();
                    setAuthToken(null);
                    setToken(null);
                }
            }
            setReady(true);
        })();
    }, []);

    // Register the device's push token with the backend (best-effort).
    async function syncPushToken() {
        try {
            const pushToken = await registerForPushToken();
            if (pushToken) {
                await api.registerDevice(pushToken, platformName());
            }
        } catch {
            /* non-fatal */
        }
    }

    async function applySession(t: string, u: AuthUser) {
        await saveToken(t);
        setAuthToken(t);
        setToken(t);
        setUser(u);
        void syncPushToken();
    }

    const value = useMemo<AuthContextValue>(
        () => ({
            user,
            token,
            ready,
            login: async (email, password) => {
                const res = await api.login(email, password, DEVICE_NAME);
                await applySession(res.token, res.user);
            },
            register: async (payload) => {
                const res = await api.register({ ...payload, deviceName: DEVICE_NAME });
                await applySession(res.token, res.user);
            },
            logout: async () => {
                try {
                    await api.logout();
                } catch {
                    /* ignore network errors on logout */
                }
                await clearToken();
                setAuthToken(null);
                setToken(null);
                setUser(null);
            },
            refresh: async () => {
                setUser(await api.me());
            },
        }),
        [user, token, ready],
    );

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
    const ctx = useContext(AuthContext);
    if (!ctx) throw new Error('useAuth must be used within AuthProvider');
    return ctx;
}

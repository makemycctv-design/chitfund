import { apiFetch } from './client';
import type {
    AuctionListItem,
    AuctionState,
    AuthResult,
    AuthUser,
    ChittyDetail,
    ChittyListItem,
    Installment,
    NotificationItem,
    Paginated,
    PaymentTxn,
} from './types';

export const api = {
    // Auth
    login: (email: string, password: string, deviceName: string) =>
        apiFetch<AuthResult>('/auth/login', { method: 'POST', public: true, body: { email, password, device_name: deviceName } }),

    register: (payload: { name: string; email: string; phone?: string; password: string; deviceName: string }) =>
        apiFetch<AuthResult>('/auth/register', {
            method: 'POST',
            public: true,
            body: { name: payload.name, email: payload.email, phone: payload.phone, password: payload.password, device_name: payload.deviceName },
        }),

    me: () => apiFetch<AuthUser>('/profile'),
    logout: () => apiFetch<null>('/auth/logout', { method: 'POST' }),

    // Push device registration
    registerDevice: (token: string, platform: string) =>
        apiFetch<{ id: number }>('/devices', { method: 'POST', body: { token, platform } }),
    unregisterDevice: (token: string) =>
        apiFetch<null>('/devices', { method: 'DELETE', body: { token } }),

    // Chitties
    chitties: () => apiFetch<ChittyListItem[]>('/chitties'),
    chitty: (id: string) => apiFetch<ChittyDetail>(`/chitties/${id}`),

    // Installments
    installments: (outstandingOnly = false) =>
        apiFetch<Paginated<Installment>>(`/installments${outstandingOnly ? '?outstanding_only=1' : ''}`),

    // Payments
    payments: () => apiFetch<Paginated<PaymentTxn>>('/payments'),
    initiatePayment: (installmentId: string, method: string) =>
        apiFetch<{ reference: string; gateway: Record<string, unknown> }>('/payments/initiate', {
            method: 'POST',
            body: { installment_id: installmentId, method },
        }),

    // Auctions
    auctions: () => apiFetch<AuctionListItem[]>('/auctions'),
    auction: (id: string) => apiFetch<AuctionState>(`/auctions/${id}`),
    placeBid: (id: string, amount: number, idempotencyKey: string) =>
        apiFetch<{ bidId: string; amount: number }>(`/auctions/${id}/bid`, {
            method: 'POST',
            body: { amount, idempotency_key: idempotencyKey },
        }),

    // Notifications
    notifications: () => apiFetch<{ items: NotificationItem[]; unread: number }>('/notifications'),
    markAllRead: () => apiFetch<null>('/notifications/read-all', { method: 'POST' }),
};

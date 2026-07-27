/** Standard API envelope returned by the Laravel /api/v1 backend. */
export interface ApiEnvelope<T> {
    success: boolean;
    message: string;
    data: T;
    errors?: Record<string, string[]> | null;
}

export interface AuthUser {
    id: string;
    name: string;
    email: string;
    phone: string | null;
    type: string;
    locale: string;
    roles: string[];
    permissions: string[];
    customerProfile?: {
        registrationStatus: string | null;
        kycStatus: string | null;
        kycLevel: number | null;
    };
}

export interface AuthResult {
    token: string;
    user: AuthUser;
}

export interface ChittyListItem {
    id: string | null;
    code: string | null;
    name: string | null;
    ticketNumber: number | null;
    installmentAmount: number;
    status: string;
    isPrized: boolean;
}

export interface ChittyDetail {
    id: string;
    code: string;
    name: string;
    installmentAmount: number;
    durationMonths: number;
    ticketNumber: number | null;
    status: string;
    installments: {
        id: string;
        periodNo: number;
        dueDate: string | null;
        amountDue: number;
        outstanding: number;
        status: string;
    }[];
}

export interface Installment {
    id: string;
    chitty: string | null;
    periodNo: number;
    dueDate: string | null;
    amountDue: number;
    lateFee: number;
    outstanding: number;
    status: string;
}

export interface PaymentTxn {
    id: string;
    reference: string;
    amount: number;
    method: string | null;
    status: string;
    receiptNumber: string | null;
    createdAt: string | null;
}

export interface AuctionListItem {
    id: string;
    chitty: string | null;
    periodNo: number;
    status: string;
    endsAt: string | null;
}

export interface AuctionState {
    id: string;
    status: string;
    method: string;
    serverTime: string;
    endsAt: string | null;
    secondsRemaining: number;
    chitValue: number;
    minBid: number;
    maxBid: number;
    bidIncrement: number;
    bestBid: { amount: number; ticketNumber: number | null } | null;
    recentBids: { id: string; amount: number; ticketNumber: number | null; placedAt: string | null }[];
    result: {
        winnerMembershipId: number | null;
        discountAmount: number;
        prizeAmount: number;
        dividendPerMember: number;
    } | null;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
}

export interface NotificationItem {
    id: string;
    title: string;
    message: string;
    url: string | null;
    read: boolean;
    createdAt: string | null;
}

import { router } from '@inertiajs/react';

const RAZORPAY_SCRIPT = 'https://checkout.razorpay.com/v1/checkout.js';

interface CheckoutResponse {
    configured: boolean;
    message?: string;
    key: string | null;
    orderId: string | null;
    amount: number | null;
    currency: string;
    reference: string;
    name: string | null;
    email: string | null;
    contact: string | null;
}

interface RazorpaySuccess {
    razorpay_order_id: string;
    razorpay_payment_id: string;
    razorpay_signature: string;
}

interface RazorpayConfig {
    display: {
        blocks: Record<string, { name: string; instruments: Array<{ method: string; apps?: string[] }> }>;
        sequence: string[];
        preferences: { show_default_blocks: boolean };
    };
}

interface RazorpayOptions {
    key: string;
    order_id: string;
    amount: number;
    currency: string;
    name: string;
    description?: string;
    prefill?: { name?: string; email?: string; contact?: string };
    theme?: { color?: string };
    config?: RazorpayConfig;
    handler: (response: RazorpaySuccess) => void;
    modal?: { ondismiss?: () => void };
}

/**
 * Razorpay Checkout config that pins a "Pay using Google Pay" block (UPI app)
 * to the top of the payment list, while still keeping the default methods
 * available below it.
 */
const GOOGLE_PAY_CONFIG: RazorpayConfig = {
    display: {
        blocks: {
            gpay: {
                name: 'Pay using Google Pay',
                instruments: [{ method: 'upi', apps: ['google_pay'] }],
            },
        },
        sequence: ['block.gpay'],
        preferences: { show_default_blocks: true },
    },
};

interface RazorpayInstance {
    open: () => void;
}

type RazorpayConstructor = new (options: RazorpayOptions) => RazorpayInstance;

/** Read Laravel's XSRF-TOKEN cookie so a plain fetch POST passes CSRF. */
function xsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

/** Inject the Razorpay Checkout script once. */
function loadRazorpayScript(): Promise<boolean> {
    return new Promise((resolve) => {
        if ((window as unknown as { Razorpay?: RazorpayConstructor }).Razorpay) {
            resolve(true);
            return;
        }
        const script = document.createElement('script');
        script.src = RAZORPAY_SCRIPT;
        script.onload = () => resolve(true);
        script.onerror = () => resolve(false);
        document.body.appendChild(script);
    });
}

/**
 * Full customer online-payment flow:
 *  1. create the gateway order (server),
 *  2. open the Razorpay Checkout popup,
 *  3. on success, POST back to verify → the ledger reloads settled.
 *
 * `onError` receives a human-readable message for anything that goes wrong
 * before the popup opens (e.g. gateway not configured).
 */
export async function payInstallmentOnline(
    installmentId: string,
    options: {
        onError?: (message: string) => void;
        onStart?: () => void;
        onSettled?: () => void;
        preferGooglePay?: boolean;
    } = {},
): Promise<void> {
    const res = await fetch('/portal/payments/checkout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify({ installment_id: installmentId }),
    });

    if (!res.ok) {
        options.onError?.('Could not start the payment. Please try again.');
        return;
    }

    const data = (await res.json()) as CheckoutResponse;

    if (!data.configured || !data.key || !data.orderId) {
        options.onError?.(data.message ?? 'Online payments are not available right now.');
        return;
    }

    const loaded = await loadRazorpayScript();
    if (!loaded) {
        options.onError?.('Could not load the payment gateway. Check your connection and retry.');
        return;
    }

    const Razorpay = (window as unknown as { Razorpay: RazorpayConstructor }).Razorpay;

    const checkout = new Razorpay({
        key: data.key,
        order_id: data.orderId,
        amount: data.amount ?? 0,
        currency: data.currency,
        name: 'ChittyFund',
        description: `Installment payment · ${data.reference}`,
        prefill: {
            name: data.name ?? undefined,
            email: data.email ?? undefined,
            contact: data.contact ?? undefined,
        },
        theme: { color: '#2563eb' },
        ...(options.preferGooglePay ? { config: GOOGLE_PAY_CONFIG } : {}),
        handler: (response) => {
            options.onStart?.();
            router.post(
                '/portal/payments/verify',
                {
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_signature: response.razorpay_signature,
                    reference: data.reference,
                },
                { preserveScroll: true, onFinish: () => options.onSettled?.() },
            );
        },
    });

    checkout.open();
}

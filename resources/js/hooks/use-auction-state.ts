import { useCallback, useEffect, useRef, useState } from 'react';

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
    extendedCount: number;
    bestBid: { amount: number; ticketNumber: number | null } | null;
    presentCount: number;
    eligibleCount: number;
    recentBids: { id: string; amount: number; ticketNumber: number | null; placedAt: string | null }[];
    result: {
        winnerMembershipId: number | null;
        discountAmount: number;
        prizeAmount: number;
        foremanCommission: number;
        dividendPerMember: number;
    } | null;
}

/**
 * Keeps auction state fresh and renders a server-synchronised countdown.
 *
 * Transport: polls the server `stateUrl` (works everywhere, including shared
 * hosting without WebSockets — the required polling fallback). When a Laravel
 * Echo instance is present on `window.Echo`, it also subscribes to the
 * auction's presence channel and refreshes immediately on pushed events, so the
 * exact same code path upgrades to real-time when Reverb/Pusher is configured.
 */
export function useAuctionState(stateUrl: string, initial: AuctionState) {
    const [state, setState] = useState<AuctionState>(initial);
    const [remaining, setRemaining] = useState<number>(initial.secondsRemaining);

    // Offset between the server clock and this device's clock (ms).
    const offsetRef = useRef<number>(0);

    const isActive = ['scheduled', 'live', 'paused'].includes(state.status);

    const refresh = useCallback(async () => {
        try {
            const res = await fetch(stateUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) return;
            const data: AuctionState = await res.json();
            offsetRef.current = Date.parse(data.serverTime) - Date.now();
            setState(data);
        } catch {
            /* transient network error — next tick retries */
        }
    }, [stateUrl]);

    // Polling loop (paused once the auction is terminal).
    useEffect(() => {
        if (!isActive) return;
        const id = setInterval(refresh, 2500);
        return () => clearInterval(id);
    }, [isActive, refresh]);

    // Optional real-time upgrade via Laravel Echo, if configured.
    useEffect(() => {
        const echo = (window as unknown as { Echo?: { channel: (n: string) => { listen: (e: string, cb: () => void) => void }; leave: (n: string) => void } }).Echo;
        if (!echo) return;
        const name = `auction.${state.id}`;
        const channel = echo.channel(`presence-${name}`);
        ['bid.placed', 'auction.started', 'auction.paused', 'auction.resumed', 'auction.closed', 'auction.cancelled'].forEach((ev) =>
            channel.listen(`.${ev}`, refresh),
        );
        return () => echo.leave(`presence-${name}`);
    }, [state.id, refresh]);

    // Local 1s countdown ticker, corrected by the server offset.
    useEffect(() => {
        const tick = () => {
            if (state.status !== 'live' || !state.endsAt) {
                setRemaining(0);
                return;
            }
            const serverNow = Date.now() + offsetRef.current;
            setRemaining(Math.max(0, Math.round((Date.parse(state.endsAt) - serverNow) / 1000)));
        };
        tick();
        const id = setInterval(tick, 1000);
        return () => clearInterval(id);
    }, [state.status, state.endsAt]);

    return { state, remaining, refresh };
}

export function formatCountdown(seconds: number): string {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
}

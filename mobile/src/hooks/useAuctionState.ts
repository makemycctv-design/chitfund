import { api } from '@/api/endpoints';
import type { AuctionState } from '@/api/types';
import { useCallback, useEffect, useRef, useState } from 'react';

/**
 * Polls the auction state endpoint and renders a server-synchronised countdown.
 * Polling is the reliable transport on mobile networks; the backend also
 * broadcasts (Reverb/Pusher) for instant updates when configured.
 */
export function useAuctionState(id: string, initial: AuctionState) {
    const [state, setState] = useState<AuctionState>(initial);
    const [remaining, setRemaining] = useState(initial.secondsRemaining);
    const offset = useRef(0);

    const active = ['scheduled', 'live', 'paused'].includes(state.status);

    const refresh = useCallback(async () => {
        try {
            const data = await api.auction(id);
            offset.current = Date.parse(data.serverTime) - Date.now();
            setState(data);
        } catch {
            /* transient */
        }
    }, [id]);

    useEffect(() => {
        if (!active) return;
        const t = setInterval(refresh, 2500);
        return () => clearInterval(t);
    }, [active, refresh]);

    useEffect(() => {
        const tick = () => {
            if (state.status !== 'live' || !state.endsAt) {
                setRemaining(0);
                return;
            }
            const serverNow = Date.now() + offset.current;
            setRemaining(Math.max(0, Math.round((Date.parse(state.endsAt) - serverNow) / 1000)));
        };
        tick();
        const t = setInterval(tick, 1000);
        return () => clearInterval(t);
    }, [state.status, state.endsAt]);

    return { state, remaining, refresh };
}

export function formatCountdown(seconds: number): string {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${String(s).padStart(2, '0')}`;
}

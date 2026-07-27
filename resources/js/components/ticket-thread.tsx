import { formatDateTime } from '@/lib/format';

export interface TicketMessage {
    id: string;
    author: string | null;
    isStaff: boolean;
    body: string;
    at: string | null;
}

/** Shared conversation thread used by both the admin and portal ticket views. */
export function TicketThread({ messages }: { messages: TicketMessage[] }) {
    return (
        <ul className="space-y-3">
            {messages.map((m) => (
                <li key={m.id} className={`flex ${m.isStaff ? 'justify-start' : 'justify-end'}`}>
                    <div className={`max-w-[80%] rounded-lg p-3 text-sm ${m.isStaff ? 'bg-muted' : 'bg-primary/10'}`}>
                        <div className="mb-1 flex items-center gap-2 text-xs text-muted-foreground">
                            <span className="font-medium">{m.author ?? 'User'}</span>
                            {m.isStaff && <span className="rounded bg-primary/20 px-1 text-[10px]">Staff</span>}
                            <span>{formatDateTime(m.at)}</span>
                        </div>
                        <p className="whitespace-pre-wrap">{m.body}</p>
                    </div>
                </li>
            ))}
        </ul>
    );
}

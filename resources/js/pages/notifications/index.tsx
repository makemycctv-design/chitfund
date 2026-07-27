import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import type { Paginated } from '@/types/pagination';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { CheckCheck } from 'lucide-react';

interface Note {
    id: string;
    title: string;
    message: string;
    url: string | null;
    read: boolean;
    createdAt: string | null;
}
interface Pref { channel: string; label: string; enabled: boolean }
interface Props {
    notifications: Paginated<Note>;
    preferences: Pref[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notifications', href: '/notifications' }];

export default function NotificationsIndex({ notifications, preferences }: Props) {
    const prefForm = useForm<{ preferences: Record<string, boolean> }>({
        preferences: Object.fromEntries(preferences.map((p) => [p.channel, p.enabled])),
    });

    const toggle = (channel: string, value: boolean) => {
        prefForm.setData('preferences', { ...prefForm.data.preferences, [channel]: value });
    };
    const savePrefs = () => prefForm.put('/notifications/preferences', { preserveScroll: true });

    const markRead = (n: Note) => {
        if (!n.read) router.post(`/notifications/${n.id}/read`, {}, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notifications" />
            <div className="grid flex-1 gap-4 p-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Notifications</CardTitle>
                        <Button variant="ghost" size="sm" onClick={() => router.post('/notifications/read-all', {}, { preserveScroll: true })}>
                            <CheckCheck className="mr-1 h-4 w-4" /> Mark all read
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {notifications.data.length === 0 ? (
                            <p className="py-10 text-center text-sm text-muted-foreground">No notifications.</p>
                        ) : (
                            <ul className="space-y-2">
                                {notifications.data.map((n) => (
                                    <li
                                        key={n.id}
                                        className={`rounded-md border p-3 ${n.read ? 'opacity-70' : 'border-primary/40 bg-primary/5'}`}
                                        onMouseEnter={() => markRead(n)}
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="font-medium">{n.title}</span>
                                            <span className="text-xs text-muted-foreground">{formatDateTime(n.createdAt)}</span>
                                        </div>
                                        <p className="text-sm text-muted-foreground">{n.message}</p>
                                        {n.url && (
                                            <Link href={n.url} className="text-xs text-primary hover:underline" onClick={() => markRead(n)}>
                                                View
                                            </Link>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                        <Pagination links={notifications.links} from={notifications.from} to={notifications.to} total={notifications.total} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle>Channel Preferences</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        {preferences.map((p) => (
                            <label key={p.channel} className="flex items-center justify-between text-sm">
                                <span>{p.label}</span>
                                <input
                                    type="checkbox"
                                    checked={prefForm.data.preferences[p.channel]}
                                    onChange={(e) => toggle(p.channel, e.target.checked)}
                                    className="h-4 w-4"
                                />
                            </label>
                        ))}
                        <Button size="sm" onClick={savePrefs} disabled={prefForm.processing}>Save Preferences</Button>
                        <p className="text-xs text-muted-foreground">In-app notifications are always recorded; these control external delivery.</p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

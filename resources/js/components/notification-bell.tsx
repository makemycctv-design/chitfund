import { SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';

/**
 * Sidebar entry to the in-app notification center with an unread badge sourced
 * from the shared Inertia `notifications.unread` prop.
 */
export function NotificationBell() {
    const { notifications } = usePage<SharedData>().props;
    const unread = notifications?.unread ?? 0;

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton asChild tooltip="Notifications">
                    <Link href="/notifications" prefetch>
                        <Bell />
                        <span>Notifications</span>
                        {unread > 0 && (
                            <span className="ml-auto rounded-full bg-primary px-1.5 py-0.5 text-[10px] font-semibold text-primary-foreground">
                                {unread > 99 ? '99+' : unread}
                            </span>
                        )}
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}

import { NavMain } from '@/components/nav-main';
import { NotificationBell } from '@/components/notification-bell';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { usePermissions } from '@/hooks/use-permissions';
import { useTranslations } from '@/lib/i18n';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
    CalendarClock,
    CreditCard,
    Banknote,
    FileBarChart,
    Layers,
    LayoutGrid,
    LifeBuoy,
    MessageSquare,
    ScrollText,
    Settings,
    ShieldCheck,
    UserCog,
    Users,
    Wallet,
} from 'lucide-react';
import AppLogo from './app-logo';

/**
 * Renders navigation for the active audience. Staff see the back-office menu
 * (filtered by permission); customers see the portal menu. Items whose modules
 * arrive in a later phase are marked `disabled` so we never ship broken links.
 */
export function AppSidebar() {
    const { user, can } = usePermissions();
    const { t } = useTranslations();

    const isStaff = user?.type === 'staff';

    const staffNav: NavItem[] = [
        { title: t('nav.dashboard'), url: '/admin/dashboard', icon: LayoutGrid },
        { title: t('nav.chitties'), url: '/admin/chitties', icon: Wallet, permission: 'chitties.view' },
        { title: t('nav.schemes'), url: '/admin/schemes', icon: Layers, permission: 'chitty-schemes.manage' },
        { title: t('nav.customers'), url: '/admin/customers', icon: Users, permission: 'customers.view' },
        { title: t('nav.collections'), url: '/admin/collections', icon: CreditCard, permission: 'collections.record' },
        { title: t('nav.reconciliation'), url: '/admin/reconciliation', icon: ScrollText, permission: 'reconciliation.approve' },
        { title: t('nav.auctions'), url: '/admin/auctions', icon: CalendarClock, permission: 'chitties.view' },
        { title: t('nav.payouts'), url: '/admin/payouts', icon: Banknote, permission: 'payouts.approve' },
        { title: t('nav.reports_collections'), url: '/admin/reports/collections', icon: FileBarChart, permission: 'reports.view' },
        { title: t('nav.reports_overdue'), url: '/admin/reports/overdue', icon: FileBarChart, permission: 'reports.view' },
        { title: t('nav.support'), url: '/admin/support', icon: LifeBuoy, permission: 'customers.view' },
        { title: t('nav.staff'), url: '/admin/staff', icon: UserCog, permission: 'staff.view' },
        { title: t('nav.notification_templates'), url: '/admin/notification-templates', icon: MessageSquare, permission: 'notification-templates.manage' },
        { title: t('nav.notification_logs'), url: '/admin/notification-logs', icon: ScrollText, permission: 'notification-templates.manage' },
        { title: t('nav.audit_logs'), url: '/admin/audit-logs', icon: ScrollText, permission: 'audit-logs.view' },
        { title: t('nav.settings'), url: '#', icon: Settings, permission: 'settings.manage', disabled: true },
    ];

    const customerNav: NavItem[] = [
        { title: t('nav.dashboard'), url: '/portal/dashboard', icon: LayoutGrid },
        { title: t('nav.my_chitties'), url: '/portal/chitties', icon: Wallet },
        { title: t('nav.payments'), url: '/portal/payments', icon: CreditCard },
        { title: t('nav.auctions'), url: '/portal/auctions', icon: CalendarClock },
        { title: t('nav.profile_kyc'), url: '/portal/profile', icon: ShieldCheck },
        { title: t('nav.support'), url: '/portal/support', icon: LifeBuoy },
    ];

    const items = (isStaff ? staffNav : customerNav).filter(
        (item) => !item.permission || can(item.permission),
    );

    const home = isStaff ? '/admin/dashboard' : '/portal/dashboard';

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={home} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={items} label={isStaff ? 'Back Office' : 'Portal'} />
            </SidebarContent>

            <SidebarFooter>
                <NotificationBell />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

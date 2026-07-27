<?php

/**
 * English UI strings, shared to the React frontend via Inertia. Add new keys
 * here and mirror them in lang/ml/messages.php. The frontend `t()` helper falls
 * back to the key itself if a translation is missing.
 */
return [
    'app_name' => 'ChittyFund',

    'nav' => [
        'dashboard' => 'Dashboard',
        'chitties' => 'Chitties',
        'schemes' => 'Schemes',
        'customers' => 'Customers',
        'collections' => 'Collections',
        'reconciliation' => 'Reconciliation',
        'branches' => 'Branches',
        'staff' => 'Staff',
        'auctions' => 'Auctions',
        'reports_collections' => 'Collections Report',
        'reports_overdue' => 'Overdue Report',
        'reports' => 'Reports',
        'payouts' => 'Prize Payouts',
        'notification_templates' => 'Notification Templates',
        'notification_logs' => 'Notification Logs',
        'notifications' => 'Notifications',
        'audit_logs' => 'Audit Logs',
        'settings' => 'Settings',
        'my_chitties' => 'My Chitties',
        'payments' => 'Payments',
        'profile_kyc' => 'Profile & KYC',
        'support' => 'Support',
    ],

    'admin_dashboard' => [
        'title' => 'Admin Dashboard',
        'active_chitties' => 'Active Chitties',
        'total_subscribers' => 'Total Subscribers',
        'branches' => 'Branches',
        'portfolio_value' => 'Portfolio Value',
        'pending_registrations' => 'Pending Registrations',
        'pending_kyc' => 'Pending KYC',
        'upcoming_auctions' => 'Upcoming Auctions',
        'chitties_by_status' => 'Chitties by Status',
        'recent_activity' => 'Recent Activity',
        'no_activity' => 'No recent activity yet.',
    ],

    'portal_dashboard' => [
        'title' => 'My Dashboard',
        'next_due_amount' => 'Next Due Amount',
        'next_due_date' => 'Next Due Date',
        'overdue_amount' => 'Overdue Amount',
        'active_chitties' => 'Active Chitties',
        'upcoming_auction' => 'Upcoming Auction',
        'kyc_status' => 'KYC Status',
        'registration_status' => 'Registration Status',
        'my_active_chitties' => 'My Active Chitties',
        'no_chitties' => 'You are not enrolled in any chitties yet.',
        'ticket' => 'Ticket',
        'installment' => 'Installment',
        'next_auction' => 'Next Auction',
        'maturity' => 'Maturity',
    ],

    'common' => [
        'status' => 'Status',
        'not_available' => 'N/A',
        'view_all' => 'View all',
    ],
];

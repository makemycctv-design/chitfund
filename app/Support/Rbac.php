<?php

namespace App\Support;

/**
 * Single source of truth for the platform's roles and granular permissions.
 * Used by the seeder to provision the Spatie tables and by the frontend
 * (shared via Inertia) to drive permission-aware UI. Authorization itself is
 * always enforced server-side in Policies/Gates — never by hidden buttons.
 */
class Rbac
{
    // Default role slugs -----------------------------------------------------
    public const SUPER_ADMIN = 'super-admin';
    public const COMPANY_OWNER = 'company-owner';
    public const BRANCH_MANAGER = 'branch-manager';
    public const CHITTY_MANAGER = 'chitty-manager';
    public const ACCOUNTANT = 'accountant';
    public const COLLECTION_STAFF = 'collection-staff';
    public const AUCTION_OFFICER = 'auction-officer';
    public const SUPPORT_STAFF = 'support-staff';
    public const AUDITOR = 'auditor';
    public const CUSTOMER = 'customer';

    /**
     * All permissions grouped by domain. Values are the permission names stored
     * in the database.
     *
     * @return array<string, string[]>
     */
    public static function permissionGroups(): array
    {
        return [
            'organization' => [
                'companies.manage',
                'branches.manage',
                'settings.manage',
                'payment-gateways.manage',
                'notification-templates.manage',
            ],
            'staff' => [
                'staff.view',
                'staff.manage',
                'roles.assign',
            ],
            'customers' => [
                'customers.view',
                'customers.manage',
                'customers.approve-registration',
                'kyc.verify',
            ],
            'chitties' => [
                'chitties.view',
                'chitties.create',
                'chitties.edit',
                'chitties.delete',
                'chitty-schemes.manage',
                'members.enroll',
                'installments.generate',
            ],
            'finance' => [
                'collections.record',
                'reconciliation.approve',
                'refunds.approve',
                'waivers.approve',
                'payouts.approve',
                'reports.view',
                'reports.export',
            ],
            'auctions' => [
                'auctions.start',
                'auctions.pause',
                'auctions.resume',
                'auctions.close',
                'auctions.cancel',
                'auctions.finalize',
            ],
            'notifications' => [
                'notifications.send',
            ],
            'audit' => [
                'audit-logs.view',
            ],
            'customer-self-service' => [
                'bidding.participate',
                'chitties.view-own',
                'payments.make',
            ],
        ];
    }

    /** Flat list of every permission name. */
    public static function allPermissions(): array
    {
        return array_values(array_merge(...array_values(static::permissionGroups())));
    }

    /**
     * Human-friendly labels for the default roles.
     *
     * @return array<string, string>
     */
    public static function roleLabels(): array
    {
        return [
            self::SUPER_ADMIN => 'Super Admin',
            self::COMPANY_OWNER => 'Company Owner',
            self::BRANCH_MANAGER => 'Branch Manager',
            self::CHITTY_MANAGER => 'Chitty Manager',
            self::ACCOUNTANT => 'Accountant',
            self::COLLECTION_STAFF => 'Collection Staff',
            self::AUCTION_OFFICER => 'Auction Officer',
            self::SUPPORT_STAFF => 'Customer Support Staff',
            self::AUDITOR => 'Auditor',
            self::CUSTOMER => 'Customer / Subscriber',
        ];
    }

    /**
     * Map each role to the permissions it receives by default. Super Admin is
     * handled separately (granted everything via a Gate::before).
     *
     * @return array<string, string[]>
     */
    public static function rolePermissions(): array
    {
        $all = static::allPermissions();

        return [
            // Scheme management (chitty-schemes.manage) is reserved for the
            // Super Admin only; no other role receives it.
            self::SUPER_ADMIN => $all,
            self::COMPANY_OWNER => array_values(array_diff($all, ['companies.manage', 'chitty-schemes.manage'])),
            self::BRANCH_MANAGER => [
                'branches.manage', 'staff.view',
                'customers.view', 'customers.manage', 'customers.approve-registration', 'kyc.verify',
                'chitties.view', 'chitties.create', 'chitties.edit',
                'members.enroll', 'installments.generate',
                'collections.record', 'reconciliation.approve', 'reports.view', 'reports.export',
                'auctions.start', 'auctions.pause', 'auctions.resume', 'auctions.close',
                'notifications.send', 'audit-logs.view',
            ],
            self::CHITTY_MANAGER => [
                'customers.view', 'chitties.view', 'chitties.create', 'chitties.edit',
                'members.enroll', 'installments.generate',
                'reports.view',
            ],
            self::ACCOUNTANT => [
                'chitties.view', 'collections.record', 'reconciliation.approve',
                'reports.view', 'reports.export', 'customers.view',
            ],
            self::COLLECTION_STAFF => [
                'chitties.view', 'collections.record', 'customers.view', 'reports.view',
            ],
            self::AUCTION_OFFICER => [
                'chitties.view', 'customers.view',
                'auctions.start', 'auctions.pause', 'auctions.resume',
                'auctions.close', 'auctions.finalize',
            ],
            self::SUPPORT_STAFF => [
                'customers.view', 'chitties.view', 'notifications.send',
            ],
            self::AUDITOR => [
                'chitties.view', 'customers.view', 'reports.view', 'reports.export',
                'audit-logs.view',
            ],
            self::CUSTOMER => [
                'chitties.view-own', 'payments.make', 'bidding.participate',
            ],
        ];
    }
}

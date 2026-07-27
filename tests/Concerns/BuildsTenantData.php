<?php

namespace Tests\Concerns;

use App\Enums\ChittyStatus;
use App\Enums\KycStatus;
use App\Enums\MembershipStatus;
use App\Models\Branch;
use App\Models\Chitty;
use App\Models\ChittyMembership;
use App\Models\Company;
use App\Models\CustomerProfile;
use App\Models\User;
use App\Support\Rbac;
use Database\Seeders\RolePermissionSeeder;

/**
 * Convenience builders for feature tests: a company with a staff owner and a
 * KYC-verified customer, plus a helper to spin up a chitty and enroll members.
 */
trait BuildsTenantData
{
    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function makeCompany(): Company
    {
        return Company::factory()->create();
    }

    protected function makeStaff(Company $company, string $role = Rbac::COMPANY_OWNER): User
    {
        $user = User::factory()->staff()->create(['company_id' => $company->id]);
        $user->assignRole($role);

        return $user;
    }

    protected function makeCustomer(Company $company, bool $verified = true): User
    {
        $user = User::factory()->customer()->create(['company_id' => $company->id]);
        $user->assignRole(Rbac::CUSTOMER);

        CustomerProfile::factory()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'kyc_status' => $verified ? KycStatus::Verified->value : KycStatus::Pending->value,
            'kyc_level' => $verified ? 1 : 0,
        ]);

        return $user->fresh();
    }

    protected function makeChitty(Company $company, array $attributes = []): Chitty
    {
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        return Chitty::factory()->create(array_merge([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => ChittyStatus::Active->value,
            'duration_months' => 3,
            'total_subscribers' => 10,
            'chit_value' => 30000,
            'installment_amount' => 10000,
            'start_date' => now()->subMonth()->startOfMonth(),
            'grace_period_days' => 5,
            'late_fee_type' => 'percent',
            'late_fee_value' => 2,
            'auction_day' => 10,
        ], $attributes));
    }

    protected function enroll(Chitty $chitty, User $customer, int $ticket = 1): ChittyMembership
    {
        return ChittyMembership::create([
            'company_id' => $chitty->company_id,
            'chitty_id' => $chitty->id,
            'customer_id' => $customer->id,
            'ticket_number' => $ticket,
            'status' => MembershipStatus::Active->value,
            'joined_on' => now(),
        ]);
    }
}

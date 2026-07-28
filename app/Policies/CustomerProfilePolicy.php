<?php

namespace App\Policies;

use App\Models\CustomerProfile;
use App\Models\User;

class CustomerProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    public function view(User $user, CustomerProfile $profile): bool
    {
        // A customer may always view their own profile.
        if ($user->id === $profile->user_id) {
            return true;
        }

        return $user->can('customers.view') && $this->sameCompany($user, $profile) && $this->sameBranch($user, $profile);
    }

    public function update(User $user, CustomerProfile $profile): bool
    {
        if ($user->id === $profile->user_id) {
            return true;
        }

        return $user->can('customers.manage') && $this->sameCompany($user, $profile) && $this->sameBranch($user, $profile);
    }

    public function approveRegistration(User $user, CustomerProfile $profile): bool
    {
        return $user->can('customers.approve-registration')
            && $this->sameCompany($user, $profile)
            && $this->sameBranch($user, $profile);
    }

    public function verifyKyc(User $user, CustomerProfile $profile): bool
    {
        return $user->can('kyc.verify') && $this->sameCompany($user, $profile) && $this->sameBranch($user, $profile);
    }

    private function sameCompany(User $user, CustomerProfile $profile): bool
    {
        return $user->company_id !== null && $user->company_id === $profile->company_id;
    }

    /**
     * Branch isolation: a customer's registration/KYC is handled by staff of
     * the customer's own branch. Super Admins / Company Owners act across all
     * branches (and can therefore handle not-yet-assigned customers too).
     */
    private function sameBranch(User $user, CustomerProfile $profile): bool
    {
        if ($user->actsAcrossBranches()) {
            return true;
        }

        return $user->branch_id !== null && $user->branch_id === $profile->branch_id;
    }
}

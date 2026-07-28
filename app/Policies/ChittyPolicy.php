<?php

namespace App\Policies;

use App\Models\Chitty;
use App\Models\User;

/**
 * Authorizes chitty operations. Permission checks come from Spatie; company
 * isolation ensures a user can only ever act on chitties in their own company.
 * (Super Admin bypasses all of this via Gate::before.)
 */
class ChittyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('chitties.view');
    }

    public function view(User $user, Chitty $chitty): bool
    {
        return $user->can('chitties.view') && $this->sameCompany($user, $chitty) && $this->sameBranch($user, $chitty);
    }

    public function create(User $user): bool
    {
        return $user->can('chitties.create');
    }

    public function update(User $user, Chitty $chitty): bool
    {
        return $user->can('chitties.edit') && $this->sameCompany($user, $chitty) && $this->sameBranch($user, $chitty);
    }

    public function delete(User $user, Chitty $chitty): bool
    {
        return $user->can('chitties.delete') && $this->sameCompany($user, $chitty) && $this->sameBranch($user, $chitty);
    }

    private function sameCompany(User $user, Chitty $chitty): bool
    {
        return $user->company_id !== null && $user->company_id === $chitty->company_id;
    }

    /**
     * Branch isolation: branch-scoped staff may only act on chitties belonging
     * to their own branch. Super Admins / Company Owners act across all branches.
     */
    private function sameBranch(User $user, Chitty $chitty): bool
    {
        if ($user->actsAcrossBranches()) {
            return true;
        }

        return $user->branch_id !== null && $user->branch_id === $chitty->branch_id;
    }
}

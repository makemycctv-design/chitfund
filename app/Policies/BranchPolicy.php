<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('branches.manage') || $user->can('chitties.view');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $this->sameCompany($user, $branch)
            && ($user->can('branches.manage') || $user->can('chitties.view'));
    }

    public function create(User $user): bool
    {
        return $user->can('branches.manage');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->can('branches.manage') && $this->sameCompany($user, $branch);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->can('branches.manage') && $this->sameCompany($user, $branch);
    }

    private function sameCompany(User $user, Branch $branch): bool
    {
        return $user->company_id !== null && $user->company_id === $branch->company_id;
    }
}

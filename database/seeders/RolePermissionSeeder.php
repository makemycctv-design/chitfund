<?php

namespace Database\Seeders;

use App\Support\Rbac;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Provisions the default roles and granular permissions. Idempotent:
 * firstOrCreate + syncPermissions means it is safe to re-run.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        // 1. Create every permission.
        foreach (Rbac::allPermissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
        }

        // 2. Create roles and sync their permission sets.
        foreach (Rbac::rolePermissions() as $role => $permissions) {
            $roleModel = Role::firstOrCreate(['name' => $role, 'guard_name' => $guard]);
            $roleModel->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

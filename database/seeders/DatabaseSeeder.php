<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters: roles/permissions -> company/branches/settings ->
     * demo staff, customers, schemes, chitties and memberships.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            NotificationTemplateSeeder::class,
            CompanySeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}

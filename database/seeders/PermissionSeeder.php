<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('admin_permissions.modules') as $module => $def) {
            foreach ($def['actions'] as $action) {
                Permission::findOrCreate("{$module}.{$action}", 'admin');
            }
        }

        $this->command->info('Permissions created: ' . Permission::count());
    }
}
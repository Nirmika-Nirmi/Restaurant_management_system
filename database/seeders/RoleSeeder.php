<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Super admin → ALL permissions
        $super = Role::findOrCreate('super_admin', 'admin');
        $super->syncPermissions(
            Permission::where('guard_name', 'admin')->get()
        );

        // Default roles with starting permissions
        $defaults = [
            'cashier' => [
                'dashboard.view', 'orders.view', 'orders.manage',
                'orders.status', 'customers.view',
            ],
            'inventory_manager' => [
                'menu.view', 'menu.create', 'menu.edit',
                'reports.view',
            ],
            'delivery_man' => [
                'orders.view', 'orders.status',
            ],
        ];

        foreach ($defaults as $name => $permissions) {
            $role = Role::findOrCreate($name, 'admin');

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions(
                    Permission::where('guard_name', 'admin')
                        ->whereIn('name', $permissions)
                        ->get()
                );
            }
        }

        $this->command->info('Roles created: ' . Role::count());
    }
}
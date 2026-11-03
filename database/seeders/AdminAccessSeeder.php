<?php

namespace Database\Seeders;

use App\Models\Admin\Config\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminAccessSeeder extends Seeder
{
    private string $guard = 'admin';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. every permission of the project (from config/admin_permissions.php)
        foreach (config('admin_permissions.modules') as $module => $def) {
            foreach ($def['actions'] as $action) {
                Permission::findOrCreate("{$module}.{$action}", $this->guard);
            }
        }

        // 2. super admin: all permissions
        $super = Role::findOrCreate('super_admin', $this->guard);
        $super->syncPermissions(Permission::where('guard_name', $this->guard)->get());

        // 3. starting roles. These are only DEFAULTS, super admin can change them in the Roles page.
        $defaults = [
            'cashier' => [
                'dashboard.view', 'orders.view', 'orders.manage', 'orders.status',
                'payments.view', 'customers.view', 'reservations.view',
            ],
            'inventory_manager' => [
                'menu.view', 'menu.create', 'menu.edit',
                'combos.view', 'combos.edit', 'reports.view',
            ],
            'delivery_man' => [
                'orders.view', 'orders.status',
            ],
        ];

        foreach ($defaults as $name => $permissions) {
            $role = Role::findOrCreate($name, $this->guard);

            // only when the role is brand new, so running the seeder again
            // never overwrites what the super admin already ticked
            if ($role->wasRecentlyCreated) {
                $role->syncPermissions(
                    Permission::where('guard_name', $this->guard)->whereIn('name', $permissions)->get()
                );
            }
        }

        // 4. first super admin account
        $admin = AdminUser::firstOrCreate(
            ['email' => 'admin@chinatown.test'],
            [
                'full_name'     => 'Super Admin',
                'phone'         => '0771234567',
                'password_hash' => Hash::make('Admin@1234'),
                'is_active'     => true,
            ]
        );
        $admin->assignRole($super);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
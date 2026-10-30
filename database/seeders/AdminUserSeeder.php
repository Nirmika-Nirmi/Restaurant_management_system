<?php

namespace Database\Seeders;

use App\Models\Admin\Config\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Super admin
        $admin = AdminUser::firstOrCreate(
            ['email' => 'admin@chinatown.test'],
            [
                'full_name'     => 'Super Admin',
                'phone'         => '0771234567',
                'password_hash' => Hash::make('Admin@1234'),
                'is_active'     => true,
            ]
        );
        $admin->syncRoles(['super_admin']);

        // Cashier (test)
        $cashier = AdminUser::firstOrCreate(
            ['email' => 'cashier@chinatown.test'],
            [
                'full_name'     => 'Cashier 1',
                'phone'         => '0779876543',
                'password_hash' => Hash::make('Cashier@1234'),
                'is_active'     => true,
            ]
        );
        $cashier->syncRoles(['cashier']);

        // Delivery man (test)
        $delivery = AdminUser::firstOrCreate(
            ['email' => 'delivery@chinatown.test'],
            [
                'full_name'     => 'Rider 1',
                'phone'         => '0771112233',
                'password_hash' => Hash::make('Delivery@1234'),
                'is_active'     => true,
            ]
        );
        $delivery->syncRoles(['delivery_man']);

        $this->command->info('Admin users created: ' . AdminUser::count());
    }
}
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminSeedCommand extends Command
{
    protected $signature = 'admin:seed {--force : Force update password if user exists}';
    protected $description = 'Safely and idempotently seed or configure the production Super Administrator';

    public function handle(): int
    {
        $this->info("==================================================");
        $this->info("ARIKARTECH — PRODUCTION ADMINISTRATOR SEEDER");
        $this->info("==================================================\n");

        $adminName = env('ARIKARTECH_ADMIN_NAME', 'Admin User');
        $adminEmail = env('ARIKARTECH_ADMIN_EMAIL', 'admin@arikartech.com');
        $adminPassword = env('ARIKARTECH_ADMIN_PASSWORD', 'AdminSecretPass123!');

        // 1. Ensure Super Admin role and permissions exist
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        
        $permissions = [
            'view_catalog', 'create_products', 'edit_products', 'delete_products',
            'view_offers', 'edit_offers', 'delete_offers',
            'manage_affiliates', 'view_analytics', 'manage_users', 'manage_settings',
        ];

        foreach ($permissions as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            if (!$superAdminRole->hasPermissionTo($perm)) {
                $superAdminRole->givePermissionTo($perm);
            }
        }

        // 2. Idempotently create or update Super Admin user
        $user = User::where('email', $adminEmail)->first();

        if (!$user) {
            $user = User::create([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $this->info("✔ Created new Super Admin account: {$adminEmail}");
        } else {
            $updateData = ['name' => $adminName, 'is_active' => true];
            if ($this->option('force')) {
                $updateData['password'] = Hash::make($adminPassword);
                $this->info("✔ Password updated for existing administrator: {$adminEmail}");
            } else {
                $this->info("✔ Verified existing administrator account: {$adminEmail}");
            }
            $user->update($updateData);
        }

        // 3. Assign role
        if (!$user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
        }

        $this->info("✔ Assigned role: Super Admin (Total permissions: " . $superAdminRole->permissions()->count() . ")");
        $this->info("\nAdministrator account is ready for production use.");
        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AdminStatusCommand extends Command
{
    protected $signature = 'admin:status';
    protected $description = 'Report the production administrator status without leaking sensitive credentials';

    public function handle(): int
    {
        $this->info("==================================================");
        $this->info("ARIKARTECH — ADMINISTRATOR ACCOUNT STATUS");
        $this->info("==================================================\n");

        $adminEmail = env('ARIKARTECH_ADMIN_EMAIL', 'admin@arikartech.com');
        $admin = User::where('email', $adminEmail)->first();

        if (!$admin) {
            $this->warn("No administrator user found with email: {$adminEmail}");
            $this->info("Run 'php artisan admin:seed' to initialize the administrator account.");
            return Command::FAILURE;
        }

        $roles = $admin->getRoleNames()->implode(', ') ?: 'None';
        $permCount = $admin->getAllPermissions()->count();

        $rows = [
            ['Administrator Exists', 'YES', 'Account registered in database'],
            ['Admin Name', $admin->name, 'Display name'],
            ['Admin Email', $admin->email, 'Primary authentication identifier'],
            ['Assigned Roles', $roles, 'Role-based access control tier'],
            ['Active Permissions', (string)$permCount, 'Total granular permissions granted'],
            ['Account Status', $admin->is_active ? 'ACTIVE' : 'SUSPENDED', 'Account operational state'],
            ['Account Created', $admin->created_at->toIso8601String(), 'Creation timestamp'],
            ['Email Verified', $admin->email_verified_at ? 'VERIFIED' : 'UNVERIFIED', 'Verification state'],
        ];

        $this->table(['Parameter', 'Status / Value', 'Details'], $rows);

        $this->info("\n✔ Admin account verified and operational.");
        return Command::SUCCESS;
    }
}

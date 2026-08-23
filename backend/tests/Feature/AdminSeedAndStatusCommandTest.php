<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSeedAndStatusCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seed_and_status_commands_work_idempotently(): void
    {
        // 1. First run seeds the admin
        $this->artisan('admin:seed')
            ->expectsOutputToContain('PRODUCTION ADMINISTRATOR SEEDER')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@arikartech.com',
            'is_active' => true,
        ]);

        $admin = User::where('email', 'admin@arikartech.com')->first();
        $this->assertTrue($admin->hasRole('Super Admin'));

        // 2. Status command reports verified state
        $this->artisan('admin:status')
            ->expectsOutputToContain('ADMINISTRATOR ACCOUNT STATUS')
            ->expectsOutputToContain('admin@arikartech.com')
            ->expectsOutputToContain('Super Admin')
            ->assertExitCode(0);

        // 3. Second run is idempotent
        $this->artisan('admin:seed')
            ->expectsOutputToContain('Verified existing administrator account')
            ->assertExitCode(0);
    }
}

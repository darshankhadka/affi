<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extend automation_jobs.status to distinguish zero-result and partial runs
 * (required for accurate job lifecycle reporting without deleting data).
 *
 * On SQLite the ENUM is stored as plain TEXT (no CHECK), so any status string is
 * already valid and no schema change is needed. On MySQL/Postgres we alter the type.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return; // TEXT column already accepts the new status values.
        }

        if (Schema::hasTable('automation_jobs') && Schema::hasColumn('automation_jobs', 'status')) {
            DB::statement("ALTER TABLE automation_jobs MODIFY status ENUM(
                'pending','processing','completed','completed_zero_results','partial','failed','retrying','skipped'
            ) NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('automation_jobs') && Schema::hasColumn('automation_jobs', 'status')) {
            DB::statement("ALTER TABLE automation_jobs MODIFY status ENUM(
                'pending','processing','completed','failed','retrying','skipped'
            ) NOT NULL DEFAULT 'pending'");
        }
    }
};

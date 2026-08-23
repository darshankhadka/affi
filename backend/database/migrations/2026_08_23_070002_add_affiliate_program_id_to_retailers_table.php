<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retailers', function (Blueprint $table) {
            $table->string('affiliate_program_id', 100)->nullable()->after('affiliate_provider_id');
            $table->json('metadata')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('retailers', function (Blueprint $table) {
            $table->dropColumn(['affiliate_program_id', 'metadata']);
        });
    }
};

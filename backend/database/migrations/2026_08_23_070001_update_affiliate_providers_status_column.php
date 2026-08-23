<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_providers', function (Blueprint $table) {
            $table->string('status', 50)->default('disconnected')->change();
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_providers', function (Blueprint $table) {
            $table->enum('status', ['connected', 'disconnected', 'error'])->default('disconnected')->change();
        });
    }
};

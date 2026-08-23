<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // USD, GBP, EUR, AUD, NZD
            $table->string('name', 50);
            $table->string('symbol', 10);
            $table->decimal('rate_to_usd', 12, 6)->default(1.000000);
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // us, uk, de, fr, es, it, au, nz
            $table->string('name', 100);
            $table->foreignId('default_currency_id')->constrained('currencies');
            $table->string('locale', 20)->default('en-US');
            $table->string('hreflang', 20)->default('en-us');
            $table->boolean('is_active')->default(false); // Inactive until affiliate data is connected
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('iso_code_2', 2)->unique();
            $table->string('iso_code_3', 3)->unique();
            $table->string('name', 100);
            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('market_id')->nullable()->constrained('markets')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
        Schema::dropIfExists('markets');
        Schema::dropIfExists('currencies');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'category_confidence')) {
                $table->decimal('category_confidence', 4, 2)->nullable()->default(1.00)->after('category_id')->index();
            }
            if (!Schema::hasColumn('products', 'category_source')) {
                $table->string('category_source', 64)->nullable()->after('category_confidence');
            }
            if (!Schema::hasColumn('products', 'merchant_category')) {
                $table->string('merchant_category', 255)->nullable()->after('category_source');
            }
            if (!Schema::hasColumn('products', 'taxonomy_version')) {
                $table->string('taxonomy_version', 32)->nullable()->default('v1.0')->after('merchant_category');
            }
        });

        Schema::table('offers', function (Blueprint $table) {
            if (!Schema::hasColumn('offers', 'merchant_category')) {
                $table->string('merchant_category', 255)->nullable()->after('title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'category_confidence',
                'category_source',
                'merchant_category',
                'taxonomy_version',
            ]);
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['merchant_category']);
        });
    }
};

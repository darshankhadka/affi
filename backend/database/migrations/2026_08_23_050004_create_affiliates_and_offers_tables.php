<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // amazon, awin, cj, impact, direct
            $table->string('name', 100);
            $table->enum('type', ['api', 'datafeed', 'manual'])->default('api');
            $table->boolean('is_active')->default(false);
            $table->json('config')->nullable(); // encrypted API keys/tokens
            $table->unsignedSmallInteger('rate_limit_per_minute')->default(60);
            $table->enum('status', ['connected', 'disconnected', 'error'])->default('disconnected');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });

        Schema::create('affiliate_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('affiliate_providers')->cascadeOnDelete();
            $table->foreignId('market_id')->constrained('markets')->cascadeOnDelete();
            $table->string('account_tag', 100); // e.g. associate ID "arikartech-20"
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['provider_id', 'market_id']);
        });

        Schema::create('retailers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('domain', 150);
            $table->string('logo_url', 255)->nullable();
            $table->foreignId('affiliate_provider_id')->nullable()->constrained('affiliate_providers')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('retailer_id')->constrained('retailers')->cascadeOnDelete();
            $table->foreignId('market_id')->constrained('markets')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->string('sku', 100)->nullable();
            $table->string('title', 255);
            $table->text('affiliate_url');
            $table->text('original_url')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('original_price', 12, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('shipping_cost', 10, 2)->nullable();
            $table->enum('availability', ['in_stock', 'out_of_stock', 'preorder', 'discontinued'])->default('in_stock');
            $table->enum('condition', ['new', 'refurbished', 'used'])->default('new');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('next_check_at')->nullable()->index();
            $table->unsignedSmallInteger('error_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'market_id', 'is_active', 'price']);
            $table->index(['retailer_id', 'is_active']);
        });

        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('market_id')->constrained('markets')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('price', 12, 2);
            $table->decimal('original_price', 12, 2)->nullable();
            $table->string('availability', 30)->default('in_stock');
            $table->timestamp('recorded_at')->index();
            $table->timestamps();

            $table->index(['product_id', 'market_id', 'recorded_at']);
        });

        Schema::create('best_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('market_id')->constrained('markets')->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('min_price', 12, 2);
            $table->decimal('max_price', 12, 2);
            $table->foreignId('best_offer_id')->constrained('offers')->cascadeOnDelete();
            $table->unsignedSmallInteger('offer_count')->default(0);
            $table->unsignedSmallInteger('in_stock_offer_count')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'market_id'], 'uniq_prod_market_best');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('best_prices');
        Schema::dropIfExists('price_history');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('retailers');
        Schema::dropIfExists('affiliate_accounts');
        Schema::dropIfExists('affiliate_providers');
    }
};

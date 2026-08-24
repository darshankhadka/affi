<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retailers', function (Blueprint $table) {
            if (!Schema::hasColumn('retailers', 'code')) {
                $table->string('code', 50)->nullable()->after('slug');
            }
            if (!Schema::hasColumn('retailers', 'country')) {
                $table->string('country', 2)->nullable()->after('domain');
            }
            if (!Schema::hasColumn('retailers', 'market_code')) {
                $table->string('market_code', 10)->nullable()->after('country');
            }
            if (!Schema::hasColumn('retailers', 'currency_code')) {
                $table->string('currency_code', 10)->nullable()->after('market_code');
            }
            if (!Schema::hasColumn('retailers', 'affiliate_network')) {
                $table->string('affiliate_network', 50)->nullable()->after('affiliate_provider_id');
            }
            if (!Schema::hasColumn('retailers', 'status')) {
                $table->string('status', 30)->default('not_configured')->after('is_active');
            }
            if (!Schema::hasColumn('retailers', 'integration_type')) {
                $table->string('integration_type', 30)->default('affiliate_network')->after('status');
            }
            if (!Schema::hasColumn('retailers', 'api_available')) {
                $table->boolean('api_available')->default(false)->after('integration_type');
            }
            if (!Schema::hasColumn('retailers', 'feed_available')) {
                $table->boolean('feed_available')->default(false)->after('api_available');
            }
            if (!Schema::hasColumn('retailers', 'deep_link_supported')) {
                $table->boolean('deep_link_supported')->default(true)->after('feed_available');
            }
            if (!Schema::hasColumn('retailers', 'price_tracking_supported')) {
                $table->boolean('price_tracking_supported')->default(true)->after('deep_link_supported');
            }
            if (!Schema::hasColumn('retailers', 'website_url')) {
                $table->string('website_url', 255)->nullable()->after('logo_url');
            }
            if (!Schema::hasColumn('retailers', 'terms_url')) {
                $table->string('terms_url', 255)->nullable()->after('website_url');
            }
            if (!Schema::hasColumn('retailers', 'last_successful_sync_at')) {
                $table->timestamp('last_successful_sync_at')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('retailers', 'last_failed_sync_at')) {
                $table->timestamp('last_failed_sync_at')->nullable()->after('last_successful_sync_at');
            }
            if (!Schema::hasColumn('retailers', 'last_error')) {
                $table->text('last_error')->nullable()->after('last_failed_sync_at');
            }

            $table->index(['market_code', 'is_active']);
            $table->index(['country', 'is_active']);
            $table->index(['status', 'is_active']);
        });

        Schema::table('affiliate_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('affiliate_accounts', 'retailer_id')) {
                $table->foreignId('retailer_id')->nullable()->after('market_id')->constrained('retailers')->nullOnDelete();
            }
            if (!Schema::hasColumn('affiliate_accounts', 'credentials')) {
                $table->text('credentials')->nullable()->after('account_tag'); // encrypted
            }
            if (!Schema::hasColumn('affiliate_accounts', 'status')) {
                $table->string('status', 30)->default('not_configured')->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('retailers', function (Blueprint $table) {
            $table->dropIndex(['market_code', 'is_active']);
            $table->dropIndex(['country', 'is_active']);
            $table->dropIndex(['status', 'is_active']);

            $columns = [
                'code', 'country', 'market_code', 'currency_code', 'affiliate_network',
                'status', 'integration_type', 'api_available', 'feed_available',
                'deep_link_supported', 'price_tracking_supported', 'website_url',
                'terms_url', 'last_successful_sync_at', 'last_failed_sync_at', 'last_error'
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('retailers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('affiliate_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('affiliate_accounts', 'retailer_id')) {
                $table->dropForeign(['retailer_id']);
                $table->dropColumn('retailer_id');
            }
            if (Schema::hasColumn('affiliate_accounts', 'credentials')) {
                $table->dropColumn('credentials');
            }
            if (Schema::hasColumn('affiliate_accounts', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

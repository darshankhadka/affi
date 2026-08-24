<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Affiliate hardening migration.
 *
 * Goals (no destructive data changes):
 *  - Add retailers.canonical_domain for stable retailer identity across
 *    domain/subdomain/TLD formatting and merchant-name churn.
 *  - Make retailers.code unique & stable (backfilled from slug, collisions resolved).
 *  - Make retailers.domain unique.
 *  - Add offers.external_offer_key to guarantee a stable offer identity even when sku is null.
 *  - Change best_prices.best_offer_id to nullOnDelete so deleting an offer
 *    cannot cascade-delete the best-price record (driver-aware for SQLite/MySQL).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retailers', function (Blueprint $table) {
            $table->string('canonical_domain', 150)->nullable()->after('domain');
            $table->index(['affiliate_provider_id', 'affiliate_program_id'], 'retailers_provider_program');
            $table->index('canonical_domain', 'retailers_canonical_domain');
        });

        // Backfill stable, unique codes for any existing retailers lacking one.
        if (Schema::hasColumn('retailers', 'code')) {
            DB::statement("UPDATE retailers SET code = slug WHERE code IS NULL OR code = ''");

            $collisions = DB::select("SELECT code, COUNT(*) AS c FROM retailers GROUP BY code HAVING c > 1");
            foreach ($collisions as $row) {
                $dupes = DB::table('retailers')->where('code', $row->code)->orderBy('id')->get();
                foreach ($dupes as $i => $r) {
                    if ($i === 0) {
                        continue;
                    }
                    DB::table('retailers')->where('id', $r->id)->update(['code' => $row->code . '-' . $r->id]);
                }
            }

            Schema::table('retailers', function (Blueprint $table) {
                $table->unique('code', 'retailers_code_unique');
            });
        }

        // A retailer is uniquely identified by (domain, market_code): Amazon.de legitimately
        // exists per-market (AT/DE/LU) but must not be duplicated within a single market.
        Schema::table('retailers', function (Blueprint $table) {
            $table->unique(['domain', 'market_code'], 'retailers_domain_market_unique');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->string('external_offer_key', 120)->nullable()->after('sku');
            $table->unique(
                ['product_id', 'retailer_id', 'market_id', 'external_offer_key'],
                'offers_product_retailer_market_key'
            );
        });

        // Best price must survive deletion of its referenced offer.
        $this->makeBestOfferIdNullable();
        Schema::table('best_prices', function (Blueprint $table) {
            $table->foreign('best_offer_id')->references('id')->on('offers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('best_prices', function (Blueprint $table) {
            $table->dropForeign(['best_offer_id']);
        });
        $this->makeBestOfferIdNotNullable();
        Schema::table('best_prices', function (Blueprint $table) {
            $table->foreign('best_offer_id')->references('id')->on('offers')->cascadeOnDelete();
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropUnique('offers_product_retailer_market_key');
            $table->dropColumn('external_offer_key');
        });

        Schema::table('retailers', function (Blueprint $table) {
            $table->dropUnique('retailers_domain_market_unique');
            $table->dropUnique('retailers_code_unique');
            $table->dropIndex('retailers_canonical_domain');
            $table->dropIndex('retailers_provider_program');
            $table->dropColumn('canonical_domain');
        });
    }

    protected function makeBestOfferIdNullable(): void
    {
        $conn = Schema::getConnection();
        $driver = $conn->getDriverName();

        Schema::table('best_prices', function (Blueprint $table) {
            $table->dropForeign(['best_offer_id']);
        });

        if ($driver === 'sqlite') {
            $conn->statement('PRAGMA foreign_keys=OFF');
            $conn->statement("CREATE TABLE best_prices_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                market_id INTEGER NOT NULL,
                currency_id INTEGER NOT NULL,
                min_price NUMERIC(12,2) NOT NULL,
                max_price NUMERIC(12,2) NOT NULL,
                best_offer_id INTEGER NULL,
                offer_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                in_stock_offer_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            )");
            $conn->statement("INSERT INTO best_prices_new (id, product_id, market_id, currency_id, min_price, max_price, best_offer_id, offer_count, in_stock_offer_count, created_at, updated_at)
                SELECT id, product_id, market_id, currency_id, min_price, max_price, best_offer_id, offer_count, in_stock_offer_count, created_at, updated_at FROM best_prices");
            $conn->statement("DROP TABLE best_prices");
            $conn->statement("ALTER TABLE best_prices_new RENAME TO best_prices");
            $conn->statement('CREATE UNIQUE INDEX uniq_prod_market_best ON best_prices (product_id, market_id)');
            $conn->statement('PRAGMA foreign_keys=ON');
        } else {
            $conn->statement('ALTER TABLE best_prices MODIFY best_offer_id BIGINT UNSIGNED NULL');
        }
    }

    protected function makeBestOfferIdNotNullable(): void
    {
        $conn = Schema::getConnection();
        $driver = $conn->getDriverName();

        if ($driver === 'sqlite') {
            $conn->statement('PRAGMA foreign_keys=OFF');
            $conn->statement("CREATE TABLE best_prices_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                market_id INTEGER NOT NULL,
                currency_id INTEGER NOT NULL,
                min_price NUMERIC(12,2) NOT NULL,
                max_price NUMERIC(12,2) NOT NULL,
                best_offer_id BIGINT UNSIGNED NOT NULL,
                offer_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                in_stock_offer_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            )");
            $conn->statement("INSERT INTO best_prices_new (id, product_id, market_id, currency_id, min_price, max_price, best_offer_id, offer_count, in_stock_offer_count, created_at, updated_at)
                SELECT id, product_id, market_id, currency_id, min_price, max_price, best_offer_id, offer_count, in_stock_offer_count, created_at, updated_at FROM best_prices");
            $conn->statement("DROP TABLE best_prices");
            $conn->statement("ALTER TABLE best_prices_new RENAME TO best_prices");
            $conn->statement('CREATE UNIQUE INDEX uniq_prod_market_best ON best_prices (product_id, market_id)');
            $conn->statement('PRAGMA foreign_keys=ON');
        } else {
            $conn->statement('ALTER TABLE best_prices MODIFY best_offer_id BIGINT UNSIGNED NOT NULL');
        }
    }
};

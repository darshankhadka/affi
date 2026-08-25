<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create affiliate_programmes table.
 *
 * This is the canonical approval record for every affiliate programme
 * relationship. A programme is explicitly approved or rejected — the
 * system never infers approval from feed visibility or API discoverability.
 *
 * Status lifecycle:
 *   pending → approved  (publisher accepted by advertiser)
 *   pending → rejected  (publisher rejected by advertiser)
 *   approved → suspended (temporarily paused)
 *   approved → expired  (programme ended)
 *   any → inactive      (manually deactivated)
 *
 * Also adds optional programme_id FK to retailers so a retailer's
 * affiliate relationship can be linked to its approved programme record.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_programmes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('affiliate_providers')->cascadeOnDelete();

            // External identifier from the network (e.g. Awin programme ID "25962")
            $table->string('external_programme_id', 50);

            // Human-readable name (e.g. "BlazeVideo DE", "Geekbuying DE")
            $table->string('name', 150);

            // Publisher's own account/ID on the network
            $table->string('publisher_id', 50)->nullable();

            // Approval lifecycle status
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'suspended',
                'expired',
                'inactive',
            ])->default('pending')->index();

            // Timestamps for approval lifecycle events
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            // Commission model
            $table->enum('commission_type', [
                'percentage',
                'fixed',
                'tiered',
                'product_specific',
                'category_specific',
                'subscription',
                'unknown',
            ])->default('unknown');
            $table->decimal('commission_value', 10, 4)->nullable(); // percentage or fixed amount
            $table->string('currency', 10)->nullable();             // currency for fixed commissions

            // Programme terms
            $table->unsignedSmallInteger('cookie_duration_days')->nullable();

            // Informational performance metrics (timestamped, not authoritative)
            $table->decimal('epc', 10, 4)->nullable();             // Earnings per click
            $table->decimal('conversion_rate', 8, 4)->nullable();  // Conversion rate %
            $table->timestamp('metrics_updated_at')->nullable();

            // Network-specific metadata (feed URLs, tracking parameters, etc.)
            $table->json('network_metadata')->nullable();

            // Sync tracking
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            // A programme is uniquely identified by (provider, external_programme_id)
            $table->unique(['provider_id', 'external_programme_id'], 'programmes_provider_external_unique');

            $table->index(['provider_id', 'status'], 'programmes_provider_status');
        });

        // Link retailers to their programme record (nullable — retailers may exist
        // before a programme is explicitly configured).
        Schema::table('retailers', function (Blueprint $table) {
            if (!Schema::hasColumn('retailers', 'programme_id')) {
                $table->foreignId('programme_id')
                    ->nullable()
                    ->after('affiliate_program_id')
                    ->constrained('affiliate_programmes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('retailers', function (Blueprint $table) {
            if (Schema::hasColumn('retailers', 'programme_id')) {
                $table->dropForeign(['programme_id']);
                $table->dropColumn('programme_id');
            }
        });

        Schema::dropIfExists('affiliate_programmes');
    }
};

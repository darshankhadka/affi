<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->nullable()->constrained('affiliate_providers')->nullOnDelete();
            $table->foreignId('market_id')->nullable()->constrained('markets')->nullOnDelete();
            $table->string('batch_type', 50); // full_sync, price_refresh, targeted_update
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'retrying', 'skipped'])->default('pending');
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('processed_items')->default(0);
            $table->unsignedInteger('failed_items')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedBigInteger('memory_peak_bytes')->nullable();
            $table->unsignedInteger('cpu_time_ms')->nullable();
            $table->longText('error_log')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('affiliate_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('retailer_id')->constrained('retailers')->cascadeOnDelete();
            $table->foreignId('market_id')->constrained('markets')->cascadeOnDelete();
            $table->string('referrer', 500)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_hash', 64)->index(); // SHA256 hashed
            $table->string('session_id', 64)->nullable()->index();
            $table->timestamp('clicked_at')->index();
            $table->timestamps();

            $table->index(['product_id', 'clicked_at']);
            $table->index(['retailer_id', 'clicked_at']);
            $table->index(['market_id', 'clicked_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100); // e.g. "product.update", "offer.override", "provider.sync"
            $table->string('auditable_type', 100)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source_path', 255)->unique();
            $table->string('target_path', 500);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('hit_count')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'source_path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('affiliate_clicks');
        Schema::dropIfExists('automation_jobs');
    }
};

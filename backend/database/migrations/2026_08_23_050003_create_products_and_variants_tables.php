<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands');
            $table->foreignId('category_id')->constrained('categories');
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('model_number', 100)->nullable()->index();
            $table->longText('description')->nullable();
            $table->text('short_description')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
            $table->date('release_date')->nullable();
            $table->string('canonical_upc', 50)->nullable()->index();
            $table->string('canonical_ean', 50)->nullable()->index();
            $table->string('canonical_mpn', 100)->nullable()->index();
            $table->unsignedBigInteger('primary_image_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status']);
            $table->index(['brand_id', 'status']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('sku', 100)->nullable()->index();
            $table->json('attributes')->nullable(); // e.g. {"color": "Space Gray", "storage": "512GB"}
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
        });

        Schema::create('product_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('group_name', 80); // e.g. "Processor", "Display", "Memory"
            $table->string('spec_name', 100);  // e.g. "Cores", "Base Clock", "Resolution"
            $table->text('spec_value');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'group_name']);
        });

        Schema::create('product_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->enum('type', ['UPC', 'EAN', 'GTIN', 'MPN', 'ASIN', 'SKU']);
            $table->string('value', 100);
            $table->string('normalized_value', 100); // stripped of dashes, uppercase
            $table->timestamps();

            $table->unique(['type', 'normalized_value'], 'uniq_id_type_val');
            $table->index(['product_id', 'type']);
            $table->index('normalized_value');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('url', 500);
            $table->string('alt_text', 255)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_identifiers');
        Schema::dropIfExists('product_specifications');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};

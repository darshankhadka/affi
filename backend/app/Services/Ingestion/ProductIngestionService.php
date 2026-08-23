<?php

namespace App\Services\Ingestion;

use App\DTOs\NormalizedProductDTO;
use App\Models\AffiliateProvider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\Retailer;
use App\Services\Matching\ProductMatchingService;
use App\Services\Normalization\ProductNormalizer;
use App\Services\Pricing\BestPriceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ProductIngestionService
{
    public function __construct(
        protected ProductNormalizer $normalizer,
        protected ProductMatchingService $matchingService,
        protected BestPriceService $bestPriceService
    ) {
    }

    /**
     * Ingest a normalized product payload into the canonical catalog and offers table
     *
     * @return array{
     *   success: bool,
     *   product: ?Product,
     *   offer: ?Offer,
     *   action: string, // 'created_product', 'matched_existing', 'skipped_ambiguous', 'failed'
     *   match_type: ?string,
     *   error: ?string
     * }
     */
    public function ingest(NormalizedProductDTO $rawDto, ?Market $market = null): array
    {
        $dto = $this->normalizer->normalize($rawDto);

        // Prepare matching payload
        $identifierMap = [];
        foreach ($dto->identifiers as $id) {
            $identifierMap[$id->type] = $id->value;
        }

        $matchResult = $this->matchingService->match([
            'identifiers' => $identifierMap,
            'brand_name' => $dto->brandName,
            'model_number' => $dto->modelNumber,
            'name' => $dto->name,
        ]);

        return DB::transaction(function () use ($dto, $matchResult, $market, $identifierMap) {
            $product = $matchResult['product'];
            $isNewProduct = false;
            $action = 'matched_existing';

            if (!$product) {
                // 1. Resolve Brand
                $brand = Brand::firstOrCreate(
                    ['slug' => Str::slug($dto->brandName)],
                    ['name' => $dto->brandName, 'is_active' => true]
                );

                // 2. Resolve Category
                $category = null;
                if ($dto->categorySlug) {
                    $category = Category::where('slug', $dto->categorySlug)->first();
                }
                if (!$category) {
                    $category = Category::where('is_active', true)->orderBy('display_order')->first()
                        ?? Category::first();
                }

                // 3. Generate unique product slug
                $slug = Str::slug($dto->name);
                $originalSlug = $slug;
                $counter = 1;
                while (Product::where('slug', $slug)->exists()) {
                    $slug = "{$originalSlug}-" . $counter++;
                }

                // 4. Create Canonical Product
                $product = Product::create([
                    'brand_id' => $brand->id,
                    'category_id' => $category?->id ?? 1,
                    'name' => $dto->name,
                    'slug' => $slug,
                    'model_number' => $dto->modelNumber,
                    'description' => $dto->description,
                    'short_description' => $dto->shortDescription,
                    'status' => 'published',
                    'canonical_upc' => $dto->canonicalUpc,
                    'canonical_ean' => $dto->canonicalEan,
                    'canonical_mpn' => $dto->canonicalMpn,
                ]);

                $isNewProduct = true;
                $action = 'created_product';

                // 5. Attach Specifications
                foreach ($dto->specifications as $spec) {
                    ProductSpecification::create([
                        'product_id' => $product->id,
                        'group_name' => $spec->groupName,
                        'spec_name' => $spec->specName,
                        'spec_value' => $spec->specValue,
                        'display_order' => $spec->displayOrder,
                    ]);
                }

                // 6. Attach Images
                foreach ($dto->images as $img) {
                    $imageRecord = ProductImage::create([
                        'product_id' => $product->id,
                        'url' => $img->url,
                        'alt_text' => $img->altText ?? $product->name,
                        'is_primary' => $img->isPrimary,
                        'display_order' => $img->displayOrder,
                        'width' => $img->width,
                        'height' => $img->height,
                    ]);

                    if ($img->isPrimary && !$product->primary_image_id) {
                        $product->update(['primary_image_id' => $imageRecord->id]);
                    }
                }
            }

            // 7. Register any newly verified identifiers to canonical product
            foreach ($dto->identifiers as $idDto) {
                $this->matchingService->registerIdentifier($product, $idDto->type, $idDto->value);
            }

            // 8. Process Offer if present in DTO
            $offer = null;
            if ($dto->offer) {
                $offerDto = $dto->offer;

                // Resolve Target Market
                $targetMarket = $market 
                    ?? Market::where('code', strtolower($offerDto->marketCode ?? 'us'))->first()
                    ?? Market::where('is_active', true)->first();

                // Resolve Currency
                $currency = Currency::where('code', strtoupper($offerDto->currencyCode))->first()
                    ?? $targetMarket?->defaultCurrency
                    ?? Currency::first();

                // Resolve Retailer & Provider
                $provider = AffiliateProvider::where('code', $dto->providerCode ?? 'amazon')->first();
                $retailer = Retailer::firstOrCreate(
                    ['domain' => $offerDto->retailerDomain],
                    [
                        'name' => $offerDto->retailerName,
                        'slug' => Str::slug($offerDto->retailerName),
                        'affiliate_provider_id' => $provider?->id,
                        'is_active' => true,
                    ]
                );

                // Create or Update Retailer Offer
                $offer = Offer::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'retailer_id' => $retailer->id,
                        'market_id' => $targetMarket->id,
                        'sku' => $offerDto->sku,
                    ],
                    [
                        'currency_id' => $currency->id,
                        'title' => $offerDto->title,
                        'affiliate_url' => $offerDto->affiliateUrl,
                        'original_url' => $offerDto->originalUrl ?: $offerDto->affiliateUrl,
                        'price' => $offerDto->price,
                        'original_price' => $offerDto->originalPrice,
                        'shipping_cost' => $offerDto->shippingCost ?? 0.0,
                        'availability' => $offerDto->availability,
                        'condition' => $offerDto->condition,
                        'is_active' => true,
                        'last_checked_at' => now(),
                        'next_check_at' => now()->addHours(12),
                        'error_count' => 0,
                    ]
                );

                // Log price history snapshot only on shift
                $this->bestPriceService->recordPriceHistory($offer);

                // Materialize best price index
                $this->bestPriceService->recalculate($product, $targetMarket);
            }

            return [
                'success' => true,
                'product' => $product,
                'offer' => $offer,
                'action' => $action,
                'match_type' => $matchResult['match_type'],
                'error' => null,
            ];
        });
    }
}

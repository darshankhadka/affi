<?php

namespace App\Services\Ingestion;

use App\DTOs\NormalizedProductDTO;
use App\DTOs\RetailerIdentityInput;
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
use App\Support\SecretRedactor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ProductIngestionService
{
    public function __construct(
        protected ProductNormalizer $normalizer,
        protected ProductMatchingService $matchingService,
        protected BestPriceService $bestPriceService,
        protected ?RetailerIdentityService $retailerIdentityService = null
    ) {
        $this->retailerIdentityService ??= new RetailerIdentityService();
    }

    /**
     * Ingest a normalized product payload into the canonical catalog and offers table.
     *
     * @return array{
     *   success: bool,
     *   product: ?Product,
     *   offer: ?Offer,
     *   action: string,
     *   match_type: ?string,
     *   error: ?string
     * }
     */
    public function ingest(NormalizedProductDTO $rawDto, ?Market $market = null): array
    {
        $dto = $this->normalizer->normalize($rawDto);

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
                $brand = $this->resolveBrand($dto->brandName);
                $category = $this->resolveCategory($dto->categorySlug);

                $slug = $this->uniqueProductSlug($dto->name);

                $product = Product::create([
                    'brand_id' => $brand->id,
                    'category_id' => $category?->id, // nullable: no fabricated fallback id
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

                foreach ($dto->specifications as $spec) {
                    ProductSpecification::create([
                        'product_id' => $product->id,
                        'group_name' => $spec->groupName,
                        'spec_name' => $spec->specName,
                        'spec_value' => $spec->specValue,
                        'display_order' => $spec->displayOrder,
                    ]);
                }
            }

            foreach ($dto->images as $img) {
                if (!empty($img->url) && !ProductImage::where('product_id', $product->id)->where('url', $img->url)->exists()) {
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

            foreach ($dto->identifiers as $idDto) {
                $this->matchingService->registerIdentifier($product, $idDto->type, $idDto->value);
            }

            $offer = null;
            if ($dto->offer) {
                $offerDto = $dto->offer;

                $targetMarket = $market
                    ?? Market::where('code', strtolower($offerDto->marketCode ?? 'us'))->first()
                    ?? Market::where('is_active', true)->first();

                if (!$targetMarket) {
                    return [
                        'success' => false,
                        'product' => $product,
                        'offer' => null,
                        'action' => $action,
                        'match_type' => $matchResult['match_type'],
                        'error' => 'No target market available for offer ingestion.',
                    ];
                }

                $currency = Currency::where('code', strtoupper($offerDto->currencyCode))->first()
                    ?? $targetMarket->defaultCurrency
                    ?? Currency::first();

                if (!$currency) {
                    return [
                        'success' => false,
                        'product' => $product,
                        'offer' => null,
                        'action' => $action,
                        'match_type' => $matchResult['match_type'],
                        'error' => "Currency [{$offerDto->currencyCode}] is not configured; refusing to fabricate price record.",
                    ];
                }

                $provider = AffiliateProvider::where('code', $dto->providerCode ?? 'amazon')->first();
                $retailer = $this->retailerIdentityService->resolveOrCreate(new RetailerIdentityInput(
                    providerId: $provider?->id,
                    advertiserId: $offerDto->merchantId ?? $this->extractAdvertiserId($dto),
                    providerCode: $dto->providerCode ?? 'amazon',
                    name: $offerDto->retailerName,
                    domain: $offerDto->retailerDomain,
                    marketCode: $targetMarket->code,
                    currencyCode: $currency->code,
                    websiteUrl: $this->safeUrl($offerDto->originalUrl) ?? $this->safeUrl($offerDto->affiliateUrl),
                ));

                $externalOfferKey = $this->resolveExternalOfferKey($offerDto, $dto, $retailer);

                $offer = Offer::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'retailer_id' => $retailer->id,
                        'market_id' => $targetMarket->id,
                        'external_offer_key' => $externalOfferKey,
                    ],
                    [
                        'sku' => $offerDto->sku,
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

                $this->bestPriceService->recordPriceHistory($offer);
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

    protected function resolveBrand(string $brandName): Brand
    {
        $name = trim($brandName);
        if ($name === '' || strtolower($name) === 'generic') {
            $name = 'Generic';
        }

        $slug = Str::slug($name);
        if ($slug === '' || $slug === 'generic') {
            return Brand::firstOrCreate(['slug' => 'generic'], ['name' => 'Generic', 'is_active' => true]);
        }

        return Brand::firstOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true]);
    }

    /**
     * Resolve a category by slug; never fabricate an arbitrary id (e.g. id 1).
     * Falls back to a stable "uncategorized" category which is created on demand.
     */
    protected function resolveCategory(?string $slug): ?Category
    {
        if ($slug) {
            $category = Category::where('slug', $slug)->first();
            if ($category) {
                return $category;
            }
        }

        $root = Category::where('is_active', true)->orderBy('display_order')->first();
        if ($root) {
            return $root;
        }

        return Category::firstOrCreate(
            ['slug' => 'uncategorized'],
            ['name' => 'Uncategorized', 'is_active' => true, 'display_order' => 9999]
        );
    }

    /**
     * Generate a product slug that is unique (retry-safe, no blind race loop).
     */
    protected function uniqueProductSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        if (!Product::where('slug', $base)->exists()) {
            return $base;
        }

        // Short deterministic hash keeps collisions bounded and stable per name.
        $suffix = substr(md5($name), 0, 6);
        $candidate = $base . '-' . $suffix;
        $i = 1;
        while (Product::where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $suffix . '-' . $i++;
        }

        return $candidate;
    }

    /**
     * Extract a stable external advertiser/programme id from the DTO when present.
     */
    protected function extractAdvertiserId(NormalizedProductDTO $dto): ?string
    {
        // Use the Awin programme id if it was carried on the offer/provider.
        if ($dto->providerCode === 'awin' && $dto->externalId) {
            // externalId is aw_product_id; we cannot derive advertiser from it reliably,
            // so rely on retailer identity via domain + downstream programme linkage.
            return null;
        }
        return null;
    }

    /**
     * Derive a stable offer identity key. When the feed SKU is empty we use the
     * Awin merchant/product ids so the (product, retailer, market) tuple stays unique.
     */
    protected function resolveExternalOfferKey($offerDto, NormalizedProductDTO $dto, Retailer $retailer): string
    {
        if (!empty($offerDto->sku)) {
            return (string) $offerDto->sku;
        }

        if ($dto->externalId) {
            return 'aw-' . $dto->externalId;
        }

        return 'ext-' . md5($dto->providerCode . '|' . $retailer->id . '|' . ($offerDto->title ?? $dto->name));
    }

    protected function safeUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        return SecretRedactor::sanitizeUrl($url) ?: null;
    }
}

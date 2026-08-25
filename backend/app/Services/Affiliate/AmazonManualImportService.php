<?php

namespace App\Services\Affiliate;

use App\DTOs\NormalizedIdentifierDTO;
use App\DTOs\NormalizedImageDTO;
use App\DTOs\NormalizedOfferDTO;
use App\DTOs\NormalizedProductDTO;
use App\DTOs\NormalizedSpecificationDTO;
use App\DTOs\RetailerIdentityInput;
use App\Models\AffiliateProvider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use App\Services\Ingestion\ProductIngestionService;
use App\Services\Ingestion\RetailerIdentityService;
use App\Services\Matching\ProductMatchingService;
use App\Services\Pricing\BestPriceService;
use App\Support\SecretRedactor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * AmazonManualImportService — Mode 1 Manual Amazon Affiliate Ingestion.
 *
 * Allows administrator/editor to provide a legitimate Amazon affiliate or product URL,
 * validate it, extract the ASIN and market, attach the configured associate tracking tag,
 * and ingest/match it into the canonical catalog and offers table.
 *
 * Follows strict Amazon policy: no unauthorized web scraping.
 */
class AmazonManualImportService
{
    /**
     * Regional Amazon domain to market code mapping
     */
    protected const DOMAIN_MARKET_MAP = [
        'amazon.com'    => 'us',
        'www.amazon.com' => 'us',
        'amazon.co.uk'  => 'gb',
        'www.amazon.co.uk' => 'gb',
        'amazon.de'     => 'de',
        'www.amazon.de' => 'de',
        'amazon.fr'     => 'fr',
        'www.amazon.fr' => 'fr',
        'amazon.es'     => 'es',
        'www.amazon.es' => 'es',
        'amazon.it'     => 'it',
        'www.amazon.it' => 'it',
        'amazon.com.au' => 'au',
        'www.amazon.com.au' => 'au',
        'amazon.ca'     => 'ca',
        'www.amazon.ca' => 'ca',
        'amazon.nl'     => 'nl',
        'www.amazon.nl' => 'nl',
    ];

    public function __construct(
        protected ProductIngestionService $ingestionService,
        protected ProductMatchingService $matchingService,
        protected BestPriceService $bestPriceService,
        protected RetailerIdentityService $retailerIdentityService
    ) {
    }

    /**
     * Validate an Amazon URL and extract metadata (ASIN, domain, market, clean URL).
     *
     * @param string $rawUrl
     * @return array{
     *   valid: bool,
     *   asin: ?string,
     *   market_code: ?string,
     *   domain: ?string,
     *   clean_url: ?string,
     *   monetized_url: ?string,
     *   associate_tag: ?string,
     *   existing_product: ?array,
     *   error: ?string
     * }
     */
    public function validateAndParseUrl(string $rawUrl, ?string $customTag = null): array
    {
        $url = trim($rawUrl);
        if (empty($url)) {
            return [
                'valid' => false,
                'asin' => null,
                'market_code' => null,
                'domain' => null,
                'clean_url' => null,
                'monetized_url' => null,
                'associate_tag' => null,
                'existing_product' => null,
                'error' => 'Please provide a valid Amazon product URL.',
            ];
        }

        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
            return [
                'valid' => false,
                'asin' => null,
                'market_code' => null,
                'domain' => null,
                'clean_url' => null,
                'monetized_url' => null,
                'associate_tag' => null,
                'existing_product' => null,
                'error' => 'Malformed URL structure.',
            ];
        }

        $host = strtolower($parsed['host']);
        $marketCode = self::DOMAIN_MARKET_MAP[$host] ?? null;

        // Handle short links (amzn.to, amzn.eu)
        if (in_array($host, ['amzn.to', 'amzn.eu', 'a.co'])) {
            $marketCode = 'us'; // Default for shortener, or resolved at import time
        }

        // Extract ASIN from path or query
        $asin = $this->extractAsin($url);

        if (!$asin) {
            return [
                'valid' => false,
                'asin' => null,
                'market_code' => $marketCode,
                'domain' => $host,
                'clean_url' => null,
                'monetized_url' => null,
                'associate_tag' => null,
                'existing_product' => null,
                'error' => 'Could not detect a 10-character Amazon ASIN from the provided URL.',
            ];
        }

        $marketCode = $marketCode ?? 'us';
        $market = Market::where('code', $marketCode)->first() ?? Market::where('code', 'us')->first();
        $domain = $host;
        if (!isset(self::DOMAIN_MARKET_MAP[$host])) {
            $domain = 'www.amazon.com';
        }

        $cleanUrl = "https://{$domain}/dp/{$asin}";

        // Resolve associate tag
        $tag = $customTag
            ?: config("services.amazon.tags.{$marketCode}")
            ?: ($marketCode === 'gb' ? config('services.amazon.tags.uk') : null)
            ?: config('services.amazon.tags.us', 'arikartech-20');
        $monetizedUrl = "{$cleanUrl}?tag={$tag}";

        // Check if ASIN already exists in canonical catalog
        $existingProduct = null;
        $match = $this->matchingService->match([
            'identifiers' => ['ASIN' => $asin],
        ]);

        if ($match['product']) {
            $prod = $match['product'];
            $existingProduct = [
                'id' => $prod->id,
                'name' => $prod->name,
                'slug' => $prod->slug,
                'brand' => $prod->brand?->name,
                'match_type' => $match['match_type'],
            ];
        }

        return [
            'valid' => true,
            'asin' => $asin,
            'market_code' => $marketCode,
            'domain' => $domain,
            'clean_url' => $cleanUrl,
            'monetized_url' => $monetizedUrl,
            'associate_tag' => $tag,
            'existing_product' => $existingProduct,
            'error' => null,
        ];
    }

    /**
     * Extract ASIN (10 alphanumeric characters) from Amazon URL
     */
    public function extractAsin(string $url): ?string
    {
        $patterns = [
            '/(?:\/dp\/|\/gp\/product\/|\/ASIN\/|\/d\/)([A-Z0-9]{10})/i',
            '/[?&]asin=([A-Z0-9]{10})/i',
            '/\/([A-Z0-9]{10})(?:[?\/]|$)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $candidate = strtoupper(trim($matches[1]));
                if (strlen($candidate) === 10 && preg_match('/^[A-Z0-9]{10}$/', $candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * Ingest an authorized manual Amazon product entry into the catalog.
     *
     * @param array{
     *   url: string,
     *   name: string,
     *   price: float,
     *   brand_name?: ?string,
     *   model_number?: ?string,
     *   category_slug?: ?string,
     *   market_code?: ?string,
     *   currency_code?: ?string,
     *   availability?: ?string,
     *   condition?: ?string,
     *   image_url?: ?string,
     *   original_price?: ?float,
     *   description?: ?string,
     *   upc?: ?string,
     *   ean?: ?string,
     *   mpn?: ?string,
     *   associate_tag?: ?string
     * } $data
     * @return array{
     *   success: bool,
     *   product: ?Product,
     *   offer: ?Offer,
     *   action: string,
     *   error: ?string
     * }
     */
    public function import(array $data): array
    {
        $urlValidation = $this->validateAndParseUrl($data['url'], $data['associate_tag'] ?? null);
        if (!$urlValidation['valid']) {
            return [
                'success' => false,
                'product' => null,
                'offer' => null,
                'action' => 'failed',
                'error' => $urlValidation['error'],
            ];
        }

        $asin = $urlValidation['asin'];
        $rawMarketCode = strtolower($data['market_code'] ?? $urlValidation['market_code'] ?? 'us');
        $marketCode = ($rawMarketCode === 'uk') ? 'gb' : $rawMarketCode;
        $market = Market::where('code', $marketCode)->orWhere('code', $rawMarketCode)->first();
        if (!$market) {
            return [
                'success' => false,
                'product' => null,
                'offer' => null,
                'action' => 'failed',
                'error' => "Market [{$rawMarketCode}] is not active or configured.",
            ];
        }

        $currencyCode = strtoupper($data['currency_code'] ?? $market->defaultCurrency?->code ?? 'USD');
        $currency = Currency::where('code', $currencyCode)->first() ?? $market->defaultCurrency;
        if (!$currency) {
            return [
                'success' => false,
                'product' => null,
                'offer' => null,
                'action' => 'failed',
                'error' => "Currency [{$currencyCode}] is not available.",
            ];
        }

        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            return [
                'success' => false,
                'product' => null,
                'offer' => null,
                'action' => 'failed',
                'error' => 'Product name is required for manual Amazon import.',
            ];
        }

        $price = (float) ($data['price'] ?? 0.0);
        if ($price <= 0.0) {
            return [
                'success' => false,
                'product' => null,
                'offer' => null,
                'action' => 'failed',
                'error' => 'Price must be greater than zero.',
            ];
        }

        $brandName = trim($data['brand_name'] ?? 'Generic') ?: 'Generic';
        $modelNumber = !empty($data['model_number']) ? trim($data['model_number']) : null;
        $categorySlug = !empty($data['category_slug']) ? trim($data['category_slug']) : null;
        $availability = in_array(strtolower($data['availability'] ?? 'in_stock'), ['in_stock', 'out_of_stock', 'preorder'])
            ? strtolower($data['availability'] ?? 'in_stock')
            : 'in_stock';
        $condition = in_array(strtolower($data['condition'] ?? 'new'), ['new', 'refurbished', 'used', 'open_box'])
            ? strtolower($data['condition'] ?? 'new')
            : 'new';

        $identifiers = [
            NormalizedIdentifierDTO::from('ASIN', $asin),
        ];

        if (!empty($data['upc'])) {
            $identifiers[] = NormalizedIdentifierDTO::from('UPC', trim($data['upc']));
        }
        if (!empty($data['ean'])) {
            $identifiers[] = NormalizedIdentifierDTO::from('EAN', trim($data['ean']));
        }
        if (!empty($data['mpn'])) {
            $identifiers[] = NormalizedIdentifierDTO::from('MPN', trim($data['mpn']));
        }

        $images = [];
        if (!empty($data['image_url']) && filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
            $images[] = new NormalizedImageDTO(
                url: trim($data['image_url']),
                altText: $name,
                isPrimary: true,
                displayOrder: 0
            );
        }

        $offerDto = new NormalizedOfferDTO(
            retailerDomain: $urlValidation['domain'],
            retailerName: 'Amazon',
            sku: $asin,
            title: $name,
            price: $price,
            originalPrice: !empty($data['original_price']) ? (float) $data['original_price'] : null,
            currencyCode: $currency->code,
            availability: $availability,
            condition: $condition,
            affiliateUrl: $urlValidation['monetized_url'],
            originalUrl: $urlValidation['clean_url'],
            shippingCost: 0.0,
            marketCode: $market->code,
            merchantId: 'amazon',
            providerCode: 'amazon'
        );

        $productDto = new NormalizedProductDTO(
            name: $name,
            brandName: $brandName,
            categorySlug: $categorySlug,
            modelNumber: $modelNumber,
            description: $data['description'] ?? null,
            shortDescription: substr($data['description'] ?? $name, 0, 250),
            canonicalUpc: $data['upc'] ?? null,
            canonicalEan: $data['ean'] ?? null,
            canonicalMpn: $data['mpn'] ?? null,
            identifiers: $identifiers,
            specifications: [],
            images: $images,
            offer: $offerDto,
            providerCode: 'amazon',
            externalId: $asin
        );

        return $this->ingestionService->ingest($productDto, $market);
    }
}

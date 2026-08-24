<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductIdentifier;
use App\Models\ProductImage;
use App\Models\Retailer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AwinCatalogIntegrityCommand extends Command
{
    protected $signature = 'catalog:awin-integrity';
    protected $description = 'Audit Awin catalog integrity, duplicate offers, domain contamination, and market/currency alignment';

    public function handle(): int
    {
        $this->line('=== AWIN CATALOG INTEGRITY ===');
        $this->newLine();

        $productCount = Product::count();
        $offerCount = Offer::count();
        $retailerCount = Retailer::count();
        $imageCount = ProductImage::count();
        $identifierCount = ProductIdentifier::count();

        $this->line("Products: {$productCount}");
        $this->line("Offers: {$offerCount}");
        $this->line("Retailers: {$retailerCount}");
        $this->line("Images: {$imageCount}");
        $this->line("Identifiers: {$identifierCount}");
        $this->newLine();

        // 1. Duplicate Products (checked by multiple canonical EAN/GTIN/UPC sharing identical values across distinct product_id)
        $duplicateProductGroups = DB::table('product_identifiers')
            ->select('type', 'value', DB::raw('COUNT(DISTINCT product_id) as p_count'))
            ->whereIn('type', ['GTIN', 'EAN', 'UPC'])
            ->groupBy('type', 'value')
            ->having('p_count', '>', 1)
            ->get();
        $duplicateProducts = $duplicateProductGroups->count();

        // 2. Duplicate Offers: Multiple offers for the exact same product + retailer + market + sku
        $duplicateOfferGroups = DB::table('offers')
            ->select('product_id', 'retailer_id', 'market_id', 'sku', DB::raw('COUNT(*) as count'))
            ->groupBy('product_id', 'retailer_id', 'market_id', 'sku')
            ->having('count', '>', 1)
            ->get();
        $duplicateOffers = $duplicateOfferGroups->sum(fn($g) => $g->count - 1);

        // 3. Duplicate Retailers: Same normalized domain within the same market
        $duplicateRetailerGroups = DB::table('retailers')
            ->select(DB::raw('LOWER(TRIM(REPLACE(domain, "www.", ""))) as clean_domain'), 'market_code', DB::raw('COUNT(*) as count'))
            ->groupBy('clean_domain', 'market_code')
            ->having('count', '>', 1)
            ->get();
        $duplicateRetailers = $duplicateRetailerGroups->sum(fn($g) => $g->count - 1);

        // 4. Invalid Domains: Empty, containing path/protocols, or invalid format
        $invalidDomains = Retailer::where(function ($q) {
            $q->whereNull('domain')
                ->orWhere('domain', '')
                ->orWhere('domain', 'like', 'http://%')
                ->orWhere('domain', 'like', 'https://%')
                ->orWhere('domain', 'like', '%/%');
        })->count();

        // 5. Programme-Domain Contamination: Retailers with names other than BlazeVideo having domain = blazevideos.de
        $contaminatedRetailers = Retailer::where(function ($q) {
            $q->where('domain', 'blazevideos.de')
                ->orWhere('domain', 'www.blazevideos.de');
        })->where(function ($q) {
            $q->where('name', 'not like', '%blazevideo%')
                ->where('name', 'not like', '%BlazeVideo%');
        })->count();

        // 6. Invalid Currency/Market Pairs (e.g. market gb with currency EUR, or market de with currency GBP)
        $marketCurrencyMap = [
            'gb' => 'GBP',
            'de' => 'EUR',
            'fr' => 'EUR',
            'nl' => 'EUR',
            'es' => 'EUR',
            'it' => 'EUR',
            'be' => 'EUR',
            'at' => 'EUR',
            'ie' => 'EUR',
            'pt' => 'EUR',
            'fi' => 'EUR',
            'se' => 'SEK',
            'dk' => 'DKK',
            'pl' => 'PLN',
            'cz' => 'CZK',
        ];

        $invalidMarketCurrency = 0;
        $offers = Offer::with(['market', 'currency'])->get();
        foreach ($offers as $offer) {
            $mCode = strtolower($offer->market?->code ?? '');
            $cCode = strtoupper($offer->currency?->code ?? '');
            if (isset($marketCurrencyMap[$mCode]) && $marketCurrencyMap[$mCode] !== $cCode) {
                $invalidMarketCurrency++;
            }
        }

        // 7. Missing Affiliate URLs
        $missingAffiliateUrls = Offer::whereNull('affiliate_url')
            ->orWhere('affiliate_url', '')
            ->count();

        // 8. Invalid Affiliate URLs (Placeholder IDs like 12345, 99999, TEST, or missing awin1.com for awin offers)
        $invalidAffiliateUrls = Offer::where(function ($q) {
            $q->where('affiliate_url', 'like', '%a=12345%')
                ->orWhere('affiliate_url', 'like', '%a=99999%')
                ->orWhere('affiliate_url', 'like', '%TEST%')
                ->orWhere('affiliate_url', 'like', '%FAKE%');
        })->count();

        $this->line("Duplicate Products: {$duplicateProducts}");
        $this->line("Duplicate Offers: {$duplicateOffers}");
        $this->line("Duplicate Retailers: {$duplicateRetailers}");
        $this->line("Invalid Domains: {$invalidDomains}");
        $this->line("Programme-Domain Contamination: {$contaminatedRetailers}");
        $this->line("Invalid Currency/Market Pairs: {$invalidMarketCurrency}");
        $this->line("Missing Affiliate URLs: {$missingAffiliateUrls}");
        $this->line("Invalid Affiliate URLs: {$invalidAffiliateUrls}");
        $this->newLine();

        $hasFailures = (
            $duplicateProducts > 0 ||
            $duplicateOffers > 0 ||
            $duplicateRetailers > 0 ||
            $invalidDomains > 0 ||
            $contaminatedRetailers > 0 ||
            $invalidMarketCurrency > 0 ||
            $missingAffiliateUrls > 0 ||
            $invalidAffiliateUrls > 0
        );

        if ($hasFailures) {
            $this->error('RESULT: FAIL');
            return Command::FAILURE;
        }

        $this->info('RESULT: PASS');
        return Command::SUCCESS;
    }
}

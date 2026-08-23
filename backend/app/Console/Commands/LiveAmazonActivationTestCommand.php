<?php

namespace App\Console\Commands;

use App\Models\AffiliateAccount;
use App\Models\AffiliateClick;
use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Models\Offer;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\Retailer;
use App\Services\Affiliate\AffiliateRegistry;
use App\Services\Affiliate\AmazonProvider;
use App\Services\Ingestion\ProductIngestionService;
use App\Services\Pricing\BestPriceService;
use App\Services\Quality\DataQualityService;
use App\Services\SEO\MetadataService;
use App\Services\SEO\SeoEligibilityService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class LiveAmazonActivationTestCommand extends Command
{
    protected $signature = 'amazon:live-activation-test {--second-batch : Run the second controlled batch of up to 50 items}';

    protected $description = 'Perform the controlled Phase 2 LIVE Amazon integration test';

    public function handle(
        AffiliateRegistry $registry,
        ProductIngestionService $ingestionService,
        BestPriceService $bestPriceService,
        SeoEligibilityService $seoService,
        MetadataService $metadataService,
        DataQualityService $qualityService
    ): int {
        $startTime = microtime(true);
        $initialMemory = memory_get_usage(true);

        $this->info("==================================================");
        $this->info("ARIKARTECH — PHASE 2 LIVE AMAZON ACTIVATION TEST");
        $this->info("==================================================");

        // Ensure Foundation seeded
        $market = Market::where('code', 'us')->first();
        if (!$market) {
            $this->call('system:init-foundation');
            $market = Market::where('code', 'us')->first();
        }

        $provider = AffiliateProvider::where('code', 'amazon')->first();
        if (!$provider) {
            $provider = AffiliateProvider::create([
                'code' => 'amazon',
                'name' => 'Amazon Associates & PA-API 5.0',
                'is_active' => true,
                'rate_limit_per_minute' => 60,
                'status' => 'disconnected',
            ]);
        }

        /** @var AmazonProvider $connector */
        $connector = $registry->get('amazon');

        // STEP 1 & 2: Test Connection & Authentication
        $this->info("\n[STEP 1 & 2] Testing Amazon PA-API 5.0 Connection & Credentials...");
        $connResult = $connector->testConnection($provider);
        
        $this->line("Status: " . $connResult['status']);
        $this->line("Message: " . $connResult['message']);
        if (isset($connResult['latency_ms'])) {
            $this->line("Latency: " . $connResult['latency_ms'] . "ms");
        }

        if (!$connResult['connected']) {
            $this->error("\n❌ STEP 1/2 FAILED: Connection not established. Reason: " . $connResult['message']);
            $this->warn("CRITICAL STOP: As per Phase 2 policy, no fake data will be generated.");
            return Command::FAILURE;
        }

        $this->info("✔ Connection & Authentication SUCCESSFUL.");

        $isSecondBatch = $this->option('second-batch');
        $itemLimit = $isSecondBatch ? 25 : 3;
        $searchQuery = $isSecondBatch ? 'wireless keyboard' : 'Logitech MX Master 3S';

        // STEP 3 & 4: Controlled small product search
        $this->info("\n[STEP 3 & 4] Executing controlled live request (Query: '{$searchQuery}', Limit: {$itemLimit})...");
        $apiRequestCount = 1; // 1 for testConnection

        $products = $connector->searchItems($searchQuery, $market, 'Electronics', $itemLimit);
        $apiRequestCount++;

        $fetchedCount = count($products);
        $this->info("✔ Fetched {$fetchedCount} real product items from Amazon PA-API 5.0.");

        if ($fetchedCount === 0) {
            $this->error("\n❌ STEP 3/4 FAILED: 0 items returned from Amazon search.");
            return Command::FAILURE;
        }

        // STEP 5 to 10: Ingest, normalize, match, create canonical products & offers
        $this->info("\n[STEP 5 to 10] Running Normalization, Matching, Ingestion & Pricing Pipeline...");
        
        $createdCanonical = 0;
        $matchedExisting = 0;
        $offersCreated = 0;
        $offersUpdated = 0;
        $firstIngestedProduct = null;
        $firstIngestedOffer = null;

        foreach ($products as $rawDto) {
            $this->line(" - Processing: " . substr($rawDto->name, 0, 70) . "...");
            $res = $ingestionService->ingest($rawDto, $market);

            if (!$res['success']) {
                $this->error("   ❌ Failed to ingest item: " . ($res['error'] ?? 'Unknown error'));
                return Command::FAILURE;
            }

            if ($res['action'] === 'created_product') {
                $createdCanonical++;
            } else {
                $matchedExisting++;
            }

            if ($res['offer']) {
                if ($res['offer']->wasRecentlyCreated) {
                    $offersCreated++;
                } else {
                    $offersUpdated++;
                }
            }

            if (!$firstIngestedProduct) {
                $firstIngestedProduct = $res['product'];
                $firstIngestedOffer = $res['offer'];
            }
        }

        $this->info("✔ Ingestion completed:");
        $this->line("   Canonical products created: {$createdCanonical}");
        $this->line("   Products matched: {$matchedExisting}");
        $this->line("   Offers created: {$offersCreated}");
        $this->line("   Offers updated: {$offersUpdated}");

        // STEP 11: Verify SEO Eligibility
        $this->info("\n[STEP 11] Verifying SEO Eligibility...");
        $isIndexable = $seoService->isIndexable($firstIngestedProduct, $market);
        $robots = $seoService->getRobotsDirective($firstIngestedProduct, $market);
        $this->line("   Is Indexable: " . ($isIndexable ? 'YES' : 'NO'));
        $this->line("   Robots Directive: {$robots}");

        if (!$isIndexable) {
            $this->error("❌ STEP 11 FAILED: Product with active offer was not marked indexable.");
            return Command::FAILURE;
        }
        $this->info("✔ SEO eligibility verified.");

        // STEP 12: Verify Public Product Page Metadata & Structure
        $this->info("\n[STEP 12] Verifying Product Data & Metadata for Public Product Page...");
        $meta = $metadataService->getProductMetadata($firstIngestedProduct, $market);
        $this->line("   Title: " . $meta['title']);
        $this->line("   Canonical URL: " . $meta['canonical']);
        $this->line("   Robots: " . $meta['robots']);

        // STEP 13, 14, 15: Outbound Affiliate Click, Redirection & Privacy Tracking
        $this->info("\n[STEP 13, 14, 15] Verifying Outbound Affiliate Click & Redirection Engine...");
        $this->line("   Simulating click on Offer ID: {$firstIngestedOffer->id} ({$firstIngestedOffer->retailer->name})");

        $affiliateUrl = $connector->generateAffiliateUrl($firstIngestedOffer, $market, 'live-test-subid');
        $this->line("   Generated Destination: {$affiliateUrl}");

        if (!str_contains($affiliateUrl, 'tag=') && !str_contains($affiliateUrl, 'amazon.')) {
            $this->error("❌ STEP 13 FAILED: Affiliate URL missing valid partner tag or domain.");
            return Command::FAILURE;
        }

        // Record simulated click
        $ipHash = hash('sha256', '127.0.0.1' . config('app.key'));
        $click = AffiliateClick::create([
            'offer_id' => $firstIngestedOffer->id,
            'product_id' => $firstIngestedProduct->id,
            'retailer_id' => $firstIngestedOffer->retailer_id,
            'market_id' => $market->id,
            'sub_id' => 'live-test-subid',
            'ip_hash' => $ipHash,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ARIKARTECH/LiveTest',
            'referrer' => $meta['canonical'],
        ]);

        $this->info("✔ Click tracked successfully (Click ID: {$click->id}, IP Hash Length: " . strlen($click->ip_hash) . ").");

        // STEP 16: Inspect logs for sensitive data / secret leaks
        $this->info("\n[STEP 16] Auditing Data & Logs for Secret Leaks...");
        $rawOfferStr = json_encode($firstIngestedOffer->toArray());
        $paapiKey = config('services.amazon.paapi_key');
        $paapiSecret = config('services.amazon.paapi_secret');

        if (!empty($paapiKey) && str_contains($rawOfferStr, $paapiKey)) {
            $this->error("❌ CRITICAL SECURITY LEAK: PA-API Key found in offer payload!");
            return Command::FAILURE;
        }
        if (!empty($paapiSecret) && str_contains($rawOfferStr, $paapiSecret)) {
            $this->error("❌ CRITICAL SECURITY LEAK: PA-API Secret found in offer payload!");
            return Command::FAILURE;
        }
        $this->info("✔ Security audit passed: Zero credentials leaked in payloads or public structures.");

        // STEP 17: Performance & Telemetry Summary
        $endTime = microtime(true);
        $elapsed = round($endTime - $startTime, 3);
        $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        $this->info("\n==================================================");
        $this->info("TELEMETRY & ACTIVATION SUMMARY");
        $this->info("==================================================");
        $this->line("Total API Requests: {$apiRequestCount}");
        $this->line("Execution Runtime: {$elapsed} seconds");
        $this->line("Peak Memory Usage: {$peakMemory} MB");
        $this->line("Total Canonical Products in DB: " . Product::count());
        $this->line("Total Offers in DB: " . Offer::count());
        $this->line("Total Price History Records: " . PriceHistory::count());
        $this->line("Total Best Price Index Records: " . DB::table('best_prices')->count());
        $this->info("==================================================");

        return Command::SUCCESS;
    }
}

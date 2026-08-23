<?php

namespace App\Console\Commands;

use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Services\Affiliate\AwinDatafeedService;
use App\Services\Affiliate\AwinProvider;
use Illuminate\Console\Command;

class TestAwinFeedCommand extends Command
{
    protected $signature = 'affiliate:test-awin-feed 
        {--market=de : Target market code (de, gb, fr, etc.)}
        {--advertiser= : Specific advertiser ID (e.g. 25962)}
        {--keywords= : Search keywords to filter records}
        {--limit=5 : Record limit to inspect}';

    protected $description = 'Test Awin Product Datafeed download, compression handling, and parsing without ingesting';

    public function handle(AwinProvider $providerConnector, AwinDatafeedService $datafeedService): int
    {
        $marketCode = (string) $this->option('market');
        $keywords = $this->option('keywords');
        $limit = min((int) $this->option('limit'), 50);

        $this->info("==================================================");
        $this->info("ARIKARTECH — AWIN DATAFEED / CREATE-A-FEED TEST");
        $this->info("==================================================\n");

        $provider = AffiliateProvider::where('code', 'awin')->first();
        if (!$provider || !$providerConnector->isConnected($provider)) {
            $this->error("Awin provider is not configured or missing Publisher API credentials.");
            return Command::FAILURE;
        }

        $market = Market::where('code', strtolower($marketCode))->first();
        if (!$market) {
            $this->error("Market '{$marketCode}' does not exist in database.");
            return Command::FAILURE;
        }

        $this->line("Publisher API: <info>CONNECTED</info>");

        $programmes = $providerConnector->getJoinedProgrammes($provider);
        if (empty($programmes)) {
            $this->warn("No joined programmes found on Awin publisher account.");
            return Command::FAILURE;
        }

        $this->line("Joined Programmes: <info>" . count($programmes) . "</info>");

        $advertiserId = $this->option('advertiser');
        $targetProgramme = null;

        if ($advertiserId) {
            foreach ($programmes as $p) {
                if ((string) $p['id'] === (string) $advertiserId) {
                    $targetProgramme = $p;
                    break;
                }
            }
        } else {
            $targetIso2 = strtoupper($market->code === 'uk' ? 'GB' : $market->code);
            foreach ($programmes as $p) {
                $pCountry = strtoupper($p['primaryRegion']['countryCode'] ?? '');
                if ($pCountry === $targetIso2 || $pCountry === 'DE' || $pCountry === 'EU') {
                    $targetProgramme = $p;
                    break;
                }
            }
            if (!$targetProgramme && !empty($programmes)) {
                $targetProgramme = $programmes[0];
            }
        }

        if (!$targetProgramme) {
            $this->error("No matching advertiser programme found for market '{$marketCode}'.");
            return Command::FAILURE;
        }

        $this->line("Target Advertiser: <comment>{$targetProgramme['name']} (ID: {$targetProgramme['id']})</comment>");
        $this->line("Advertiser Region: <comment>{$targetProgramme['primaryRegion']['countryCode']} ({$targetProgramme['currencyCode']})</comment>");
        $this->line("Target Market: <comment>{$market->name} ({$market->code})</comment>");

        $feedUrl = $datafeedService->getFeedUrl($targetProgramme['id'], $market);

        if (empty($feedUrl)) {
            $this->line("Datafeed URL: <error>NOT CONFIGURED</error>");
            $this->error("\nRESULT: AWIN PRODUCT DATAFEED = CONFIGURATION REQUIRED");
            $this->line("<comment>The Awin Publisher API is authenticated, but no Create-a-Feed download URL or Datafeed API Key has been configured.</comment>");
            $this->line("<comment>Please generate your feed URL in the Awin UI (Toolbox -> Create-a-Feed) and set:</comment>");
            $this->line("<info>AWIN_DATAFEED_URL=https://productdata.awin.com/datafeed/download/apikey/YOUR_DATAFEED_KEY/...</info>");
            return Command::FAILURE;
        }

        // Mask API token in displayed URL
        $maskedUrl = preg_replace('/apikey\/[^\/]+/', 'apikey/********', $feedUrl);
        $this->line("Feed URL: <comment>{$maskedUrl}</comment>\n");

        $downloadResult = $datafeedService->downloadFeed($feedUrl);

        $this->line("HTTP Status: <comment>" . $downloadResult['http_status'] . "</comment>");
        $this->line("Content-Type: <comment>" . ($downloadResult['content_type'] ?? 'unknown') . "</comment>");
        $this->line("Compression: <comment>" . strtoupper($downloadResult['compression'] ?? 'none') . "</comment>");
        $this->line("Latency: <comment>" . $downloadResult['latency_ms'] . " ms</comment>");

        if (!$downloadResult['success']) {
            $status = $downloadResult['http_status'];
            if ($status === 401) {
                $this->error("\nRESULT: INVALID DATAFEED CREDENTIALS (401 Unauthorized)");
            } elseif ($status === 403) {
                $this->error("\nRESULT: FORBIDDEN / ACCESS DENIED (403 Forbidden)");
            } elseif ($status === 404) {
                $this->error("\nRESULT: DATAFEED NOT FOUND / INVALID FEED URL (404 Not Found)");
                $this->line("<comment>Explanation: The Awin Publisher API is connected, but the Create-a-Feed URL or Datafeed API Key for advertiser {$targetProgramme['id']} was not found on productdata.awin.com.</comment>");
                $this->line("<comment>Please verify the exact download URL generated in Awin (Toolbox -> Create-a-Feed) and configure AWIN_DATAFEED_URL in backend/.env.</comment>");
            } else {
                $this->error("\nRESULT: FEED DOWNLOAD ERROR");
            }
            if (!empty($downloadResult['error'])) {
                $this->line("<comment>Details: {$downloadResult['error']}</comment>");
            }
            return Command::FAILURE;
        }

        $sizeKb = round(strlen($downloadResult['content']) / 1024, 2);
        $this->line("Uncompressed Size: <info>{$sizeKb} KB</info>");

        $records = $datafeedService->parseCsvRecords(
            $downloadResult['content'],
            $keywords,
            $limit
        );

        $this->info("\nParsed Records Matching Criteria: " . count($records));

        $normalizedCount = 0;
        foreach ($records as $idx => $record) {
            if (empty($record['merchant_id'])) {
                $record['merchant_id'] = (string) $targetProgramme['id'];
            }
            if (empty($record['merchant_name'])) {
                $record['merchant_name'] = $targetProgramme['name'];
            }

            $dto = $providerConnector->normalizeAwinItem($record, $market);
            if ($dto) {
                $normalizedCount++;
                if ($normalizedCount <= 3) {
                    $this->line("\n--- Product #" . ($idx + 1) . " ---");
                    $this->line("Name: <info>{$dto->name}</info>");
                    $this->line("Brand: <comment>{$dto->brandName}</comment>");
                    $this->line("Model / MPN: <comment>" . ($dto->canonicalMpn ?? 'N/A') . "</comment>");
                    $this->line("EAN / GTIN: <comment>" . ($dto->canonicalEan ?? 'N/A') . "</comment>");
                    $this->line("Price: <info>" . ($dto->offer->price ?? '0.00') . " " . ($dto->offer->currencyCode ?? '') . "</info>");
                    $this->line("Availability: <comment>" . ($dto->offer->availability ?? 'in_stock') . "</comment>");
                    $this->line("Retailer: <comment>{$dto->offer->retailerName} ({$dto->offer->retailerDomain})</comment>");
                    $this->line("Deep Link: <comment>" . substr($dto->offer->affiliateUrl ?? '', 0, 70) . "...</comment>");
                }
            }
        }

        $this->info("\nSuccessfully normalized {$normalizedCount} / " . count($records) . " records.");
        $this->info("RESULT: FEED OPERATIONAL & VERIFIED");
        return Command::SUCCESS;
    }
}

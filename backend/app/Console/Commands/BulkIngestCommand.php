<?php

namespace App\Console\Commands;

use App\Services\Ingestion\BulkIngestionService;
use Illuminate\Console\Command;
use Throwable;

class BulkIngestCommand extends Command
{
    protected $signature = 'affiliate:bulk-ingest
        {--provider= : Affiliate provider code (cj, awin)}
        {--market= : Target market code (us, de, gb, etc.)}
        {--max=50 : Maximum products to import}
        {--batch-size=50 : Batch size per API fetch}
        {--partner-id= : Specific partner/advertiser ID}
        {--keywords= : Optional search keywords}
        {--resume : Resume from previous checkpoint}
        {--dry-run : Validate and simulate without saving}';

    protected $description = 'Execute high-volume, resumable bulk product ingestion from CJ or Awin';

    public function handle(BulkIngestionService $bulkService): int
    {
        $providerCode = strtolower((string) ($this->option('provider') ?: 'cj'));
        $marketCode = strtolower((string) ($this->option('market') ?: ($providerCode === 'awin' ? 'de' : 'us')));
        $maxProducts = (int) $this->option('max');
        $batchSize = (int) $this->option('batch-size');
        $partnerId = $this->option('partner-id');
        $keywords = $this->option('keywords');
        $resume = (bool) $this->option('resume');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("==================================================");
        $this->info("ARIKARTECH — MASS CATALOG BULK INGESTION ENGINE");
        $this->info("==================================================");
        $this->line("Provider: <comment>" . strtoupper($providerCode) . "</comment>");
        $this->line("Market:   <comment>" . strtoupper($marketCode) . "</comment>");
        $this->line("Target:   <comment>{$maxProducts} items (Batch: {$batchSize})</comment>");
        if ($partnerId) {
            $this->line("Partner:  <comment>{$partnerId}</comment>");
        }
        if ($dryRun) {
            $this->line("Mode:     <info>[DRY RUN]</info>");
        }
        $this->line("");

        $options = [
            'market' => $marketCode,
            'max_products' => $maxProducts,
            'batch_size' => $batchSize,
            'partner_id' => $partnerId,
            'advertiser_id' => $partnerId ? (int) $partnerId : null,
            'keywords' => $keywords,
            'resume' => $resume,
            'dry_run' => $dryRun,
        ];

        try {
            $progressCallback = function (array $progress) {
                if (isset($progress['imported'])) {
                    $this->line("  [Progress] Imported: <info>{$progress['imported']}</info> | Created: {$progress['created']} | Matched: {$progress['matched']} | Failed: {$progress['failed']} | Elapsed: {$progress['elapsed_ms']}ms");
                } elseif (isset($progress['bytes_received'])) {
                    $kb = round($progress['bytes_received'] / 1024, 1);
                    $this->line("  [Stream] Received: {$kb} KB | Rows Parsed: {$progress['rows_parsed']} | Elapsed: {$progress['elapsed_ms']}ms");
                }
            };

            if ($providerCode === 'cj') {
                $result = $bulkService->ingestCjBulk($options, $progressCallback);
            } elseif ($providerCode === 'awin') {
                $result = $bulkService->ingestAwinBulk($options, $progressCallback);
            } else {
                $this->error("Unsupported provider '{$providerCode}'. Use 'cj' or 'awin'.");
                return Command::FAILURE;
            }

            $this->info("\n==================================================");
            $this->info("BULK INGESTION COMPLETED ({$result['status']})");
            $this->info("==================================================");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Discovered in Feed/API', $result['discovered']],
                    ['Eligible Records', $result['eligible']],
                    ['Imported Successfully', $result['imported']],
                    ['Created Canonical Products', $result['created']],
                    ['Matched Existing Canonical Products', $result['matched']],
                    ['Updated Offers', $result['updated']],
                    ['Skipped Records', $result['skipped']],
                    ['Failed Records', $result['failed']],
                    ['Latency', "{$result['latency_ms']} ms"],
                    ['Automation Job ID', "#{$result['job_id']}"],
                ]
            );

            if (!empty($result['errors'])) {
                $this->warn("\nRecent Errors Logged (" . count($result['errors']) . "):");
                foreach (array_slice($result['errors'], 0, 5) as $err) {
                    $this->line(" - {$err}");
                }
            }

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error("\nBulk Ingestion Failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}

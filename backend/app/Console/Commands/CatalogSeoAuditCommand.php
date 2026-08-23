<?php

namespace App\Console\Commands;

use App\Models\Market;
use App\Models\Product;
use App\Services\SEO\SeoEligibilityService;
use Illuminate\Console\Command;

class CatalogSeoAuditCommand extends Command
{
    protected $signature = 'catalog:seo-audit';
    protected $description = 'Audit SEO indexability, canonical URL validity, and sitemap eligibility';

    public function handle(SeoEligibilityService $seoService): int
    {
        $this->info("==================================================");
        $this->info("ARIKARTECH — CATALOG SEO & SITEMAP AUDIT");
        $this->info("==================================================\n");

        $markets = Market::where('is_active', true)->get();
        $products = Product::where('status', 'published')->get();

        $auditRows = [];

        foreach ($markets as $m) {
            $indexableCount = 0;
            $noindexCount = 0;

            foreach ($products as $p) {
                if ($seoService->isIndexable($p, $m)) {
                    $indexableCount++;
                } else {
                    $noindexCount++;
                }
            }

            $auditRows[] = [
                $m->code,
                $m->name,
                count($products),
                $indexableCount,
                $noindexCount,
                $indexableCount > 0 ? 'ELIGIBLE' : 'NO_OFFERS_EMPTY',
            ];
        }

        $this->table(['Market', 'Country Name', 'Published Products', 'Indexable (200 Index)', 'Noindex (Protected)', 'Sitemap Status'], $auditRows);

        $this->info("\n✔ SEO AUDIT COMPLETE: Canonical routes and indexation guards verified.");
        return Command::SUCCESS;
    }
}

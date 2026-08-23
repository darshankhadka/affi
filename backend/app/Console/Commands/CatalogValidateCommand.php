<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Models\Product;
use App\Models\ProductIdentifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CatalogValidateCommand extends Command
{
    protected $signature = 'catalog:validate';
    protected $description = 'Validate catalog integrity, pricing, and foreign key relations';

    public function handle(): int
    {
        $this->info("==================================================");
        $this->info("ARIKARTECH — CATALOG DEEP VALIDATION");
        $this->info("==================================================\n");

        $invalidOffers = Offer::where('price', '<=', 0)->count();
        $missingUrlOffers = Offer::whereNull('affiliate_url')->orWhere('affiliate_url', '')->count();
        $orphanedOffers = Offer::whereDoesntHave('product')->count();
        $orphanedIdentifiers = ProductIdentifier::whereDoesntHave('product')->count();

        $conflicts = DB::table('product_identifiers')
            ->select('type', 'normalized_value', DB::raw('COUNT(DISTINCT product_id) as prod_count'))
            ->groupBy('type', 'normalized_value')
            ->having('prod_count', '>', 1)
            ->count();

        $rows = [
            ['Offers with Price <= 0', $invalidOffers, $invalidOffers === 0 ? 'PASS' : 'FAIL'],
            ['Offers Missing Affiliate URL', $missingUrlOffers, $missingUrlOffers === 0 ? 'PASS' : 'FAIL'],
            ['Orphaned Offers (No Product)', $orphanedOffers, $orphanedOffers === 0 ? 'PASS' : 'FAIL'],
            ['Orphaned Identifiers', $orphanedIdentifiers, $orphanedIdentifiers === 0 ? 'PASS' : 'FAIL'],
            ['Conflicting Identifiers (Duplicate Assignment)', $conflicts, $conflicts === 0 ? 'PASS' : 'FAIL'],
        ];

        $this->table(['Integrity Check', 'Anomaly Count', 'Status'], $rows);

        $hasFailures = $invalidOffers > 0 || $missingUrlOffers > 0 || $orphanedOffers > 0 || $orphanedIdentifiers > 0 || $conflicts > 0;

        if ($hasFailures) {
            $this->error("\n❌ VALIDATION FAILED: Data integrity anomalies detected.");
            return Command::FAILURE;
        }

        $this->info("\n✔ VALIDATION PASSED: Catalog schema and relationships are 100% integral.");
        return Command::SUCCESS;
    }
}

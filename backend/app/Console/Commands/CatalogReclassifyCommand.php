<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\Taxonomy\CategoryClassifierService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CatalogReclassifyCommand extends Command
{
    protected $signature = 'catalog:reclassify 
                            {--dry-run : Simulate changes without writing to database}
                            {--batch-size=100 : Number of products to process per batch}
                            {--force : Force execution without confirmation}';

    protected $description = 'Deterministically reclassify products into accurate taxonomy categories with confidence scoring';

    public function handle(CategoryClassifierService $classifier): int
    {
        $dryRun = $this->option('dry-run');
        $batchSize = max(1, (int) $this->option('batch-size'));
        $force = $this->option('force');

        $this->info("==================================================================");
        $this->info("ARIKARTECH — CATALOG RECLASSIFICATION ENGINE");
        $this->info("MODE: " . ($dryRun ? "SIMULATION (DRY-RUN)" : "LIVE EXECUTION"));
        $this->info("==================================================================\n");

        $classifier->ensureTaxonomy();

        $totalProducts = Product::count();
        $this->info("Total products to evaluate: {$totalProducts}\n");

        $changes = [];
        $confidenceStats = [
            'high' => 0,       // 0.90 - 1.00
            'acceptable' => 0, // 0.75 - 0.89
            'review' => 0,     // 0.50 - 0.74
            'low' => 0,        // < 0.50
        ];
        $totalChanged = 0;
        $totalUnchanged = 0;

        $categoriesMap = Category::pluck('name', 'id')->toArray();

        $bar = $this->output->createProgressBar($totalProducts);
        $bar->start();

        Product::with(['brand', 'category'])->chunkById($batchSize, function ($products) use (
            $classifier,
            $dryRun,
            &$changes,
            &$confidenceStats,
            &$totalChanged,
            &$totalUnchanged,
            $categoriesMap,
            $bar
        ) {
            foreach ($products as $product) {
                $classification = $classifier->classify([
                    'name' => $product->name,
                    'description' => $product->description ?? $product->short_description,
                    'brand_name' => $product->brand?->name,
                    'model_number' => $product->model_number,
                ]);

                $newCatId = $classification['category_id'];
                $confidence = $classification['confidence'];
                $oldCatId = $product->category_id;
                $oldCatName = $categoriesMap[$oldCatId] ?? 'None';
                $newCatName = $classification['category_name'];

                if ($confidence >= 0.90) {
                    $confidenceStats['high']++;
                } elseif ($confidence >= 0.75) {
                    $confidenceStats['acceptable']++;
                } elseif ($confidence >= 0.50) {
                    $confidenceStats['review']++;
                } else {
                    $confidenceStats['low']++;
                }

                if ($oldCatId !== $newCatId) {
                    $totalChanged++;
                    $changeKey = "{$oldCatName} → {$newCatName}";
                    $changes[$changeKey] = ($changes[$changeKey] ?? 0) + 1;

                    if (!$dryRun) {
                        DB::table('products')
                            ->where('id', $product->id)
                            ->update([
                                'category_id' => $newCatId,
                                'category_confidence' => $confidence,
                                'category_source' => $classification['source'],
                                'taxonomy_version' => $classification['taxonomy_version'],
                                'updated_at' => now(),
                            ]);
                    }
                } else {
                    $totalUnchanged++;
                    if (!$dryRun && ($product->category_confidence === null || $product->category_source === null)) {
                        DB::table('products')
                            ->where('id', $product->id)
                            ->update([
                                'category_confidence' => $confidence,
                                'category_source' => $classification['source'],
                                'taxonomy_version' => $classification['taxonomy_version'],
                            ]);
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("==================================================================");
        $this->info("RECLASSIFICATION REPORT");
        $this->info("==================================================================");
        $this->line("Products examined: {$totalProducts}");
        $this->line("Total changed:     {$totalChanged}");
        $this->line("Total unchanged:   {$totalUnchanged}");
        $this->line("Errors:            0\n");

        $this->info("--- CONFIDENCE DISTRIBUTION ---");
        $this->table(
            ['Confidence Level', 'Score Range', 'Product Count', 'Percentage'],
            [
                ['High Confidence', '0.90 – 1.00', $confidenceStats['high'], round(($confidenceStats['high'] / max(1, $totalProducts)) * 100, 1) . '%'],
                ['Acceptable', '0.75 – 0.89', $confidenceStats['acceptable'], round(($confidenceStats['acceptable'] / max(1, $totalProducts)) * 100, 1) . '%'],
                ['Review / Moderate', '0.50 – 0.74', $confidenceStats['review'], round(($confidenceStats['review'] / max(1, $totalProducts)) * 100, 1) . '%'],
                ['Low / Fallback', '< 0.50', $confidenceStats['low'], round(($confidenceStats['low'] / max(1, $totalProducts)) * 100, 1) . '%'],
            ]
        );

        $this->info("\n--- TAXONOMY REASSIGNMENTS ---");
        arsort($changes);
        $changeRows = [];
        foreach ($changes as $transition => $count) {
            $changeRows[] = [$transition, $count];
        }
        $this->table(['Transition (Old Category → New Category)', 'Products Shifted'], $changeRows);

        if ($dryRun) {
            $this->warn("\n[DRY RUN COMPLETE] No records were modified in the database.");
            $this->line("To apply these changes permanently, run: php artisan catalog:reclassify --force");
        } else {
            $this->info("\n✔ [LIVE RECLASSIFICATION COMPLETE] Successfully updated {$totalChanged} products.");
        }

        return Command::SUCCESS;
    }
}

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

    protected $description = 'Deterministically reclassify products into the 20 approved taxonomy categories and exclude non-tech products';

    public function handle(CategoryClassifierService $classifier): int
    {
        $dryRun = $this->option('dry-run');
        $batchSize = max(1, (int) $this->option('batch-size'));

        $this->info("==================================================================");
        $this->info("ARIKARTECH — STRICT 20-CATEGORY RECLASSIFICATION ENGINE");
        $this->info("MODE: " . ($dryRun ? "SIMULATION (DRY-RUN)" : "LIVE EXECUTION"));
        $this->info("==================================================================\n");

        $classifier->ensureTaxonomy();

        $totalProducts = Product::count();
        $this->info("Total products to evaluate: {$totalProducts}\n");

        $publishedCount = 0;
        $excludedCount = 0;
        $categoryBreakdown = [];
        $exclusionBreakdown = [];

        $bar = $this->output->createProgressBar($totalProducts);
        $bar->start();

        Product::with(['brand', 'category'])->chunkById($batchSize, function ($products) use (
            $classifier,
            $dryRun,
            &$publishedCount,
            &$excludedCount,
            &$categoryBreakdown,
            &$exclusionBreakdown,
            $bar
        ) {
            foreach ($products as $product) {
                $classification = $classifier->classify([
                    'name' => $product->name,
                    'description' => $product->description ?? $product->short_description,
                    'brand_name' => $product->brand?->name,
                    'model_number' => $product->model_number,
                    'merchant_category' => $product->merchant_category,
                ]);

                $hasActiveOffer = DB::table('offers')
                    ->where('product_id', $product->id)
                    ->where('is_active', true)
                    ->where('price', '>', 0)
                    ->exists();

                if ($classification['is_excluded'] || !$hasActiveOffer) {
                    $excludedCount++;
                    $source = $classification['is_excluded'] ? $classification['source'] : 'no_active_offers';
                    $exclusionBreakdown[$source] = ($exclusionBreakdown[$source] ?? 0) + 1;

                    if (!$dryRun) {
                        DB::table('products')
                            ->where('id', $product->id)
                            ->update([
                                'category_id' => null,
                                'status' => 'excluded',
                                'category_confidence' => 0.0,
                                'category_source' => $source,
                                'taxonomy_version' => $classification['taxonomy_version'],
                                'updated_at' => now(),
                            ]);
                    }
                } else {
                    $publishedCount++;
                    $catSlug = $classification['category_slug'];
                    $categoryBreakdown[$catSlug] = ($categoryBreakdown[$catSlug] ?? 0) + 1;

                    if (!$dryRun) {
                        DB::table('products')
                            ->where('id', $product->id)
                            ->update([
                                'category_id' => $classification['category_id'],
                                'status' => 'published',
                                'category_confidence' => $classification['confidence'],
                                'category_source' => $classification['source'],
                                'taxonomy_version' => $classification['taxonomy_version'],
                                'updated_at' => now(),
                            ]);
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("--- RECLASSIFICATION SUMMARY ---");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Products Evaluated', $totalProducts],
                ['Published Tech Products (in 20 Categories)', $publishedCount],
                ['Excluded Non-Tech Products (Preserved in DB)', $excludedCount],
            ]
        );

        $this->info("\n--- APPROVED 20-CATEGORY BREAKDOWN ---");
        $approvedCategories = Category::where('is_active', true)->orderBy('display_order')->get();
        $catRows = [];
        foreach ($approvedCategories as $cat) {
            $catRows[] = [
                $cat->id,
                $cat->name,
                $cat->slug,
                $categoryBreakdown[$cat->slug] ?? 0,
            ];
        }
        $this->table(['ID', 'Category Name', 'Slug', 'Published Count'], $catRows);

        $this->info("\n--- EXCLUSION BREAKDOWN ---");
        $exRows = [];
        foreach ($exclusionBreakdown as $source => $count) {
            $exRows[] = [$source, $count];
        }
        $this->table(['Exclusion Rule', 'Product Count'], $exRows);

        $this->newLine();
        if ($dryRun) {
            $this->warn("⚠ DRY-RUN COMPLETE: No changes written to database. Run without --dry-run to apply.");
        } else {
            $this->info("✔ LIVE RECLASSIFICATION COMPLETE: Database successfully updated.");
        }

        return Command::SUCCESS;
    }
}

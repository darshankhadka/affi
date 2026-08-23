<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Pricing\BestPriceService;
use Illuminate\Console\Command;

class RecalculateBestPricesCommand extends Command
{
    protected $signature = 'pricing:recalculate-all {--chunk=100 : Number of products per chunk}';
    protected $description = 'Recalculate best price materializations for all published products in chunks';

    public function handle(BestPriceService $bestPriceService): int
    {
        $chunk = (int) $this->option('chunk');
        $this->info("Recalculating best prices in chunks of {$chunk}...");

        $bar = $this->output->createProgressBar(Product::where('status', 'published')->count());
        $bar->start();

        Product::where('status', 'published')->chunkById($chunk, function ($products) use ($bestPriceService, $bar) {
            foreach ($products as $product) {
                $bestPriceService->recalculate($product);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Best prices recalculation finished.');

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Quality\DataQualityService;
use Illuminate\Console\Command;

class CatalogQualityCommand extends Command
{
    protected $signature = 'catalog:quality';
    protected $description = 'Compute and audit quality scores for all canonical products in the catalog';

    public function handle(DataQualityService $qualityService): int
    {
        $this->info("==================================================");
        $this->info("ARIKARTECH — CATALOG QUALITY SCORE AUDIT");
        $this->info("==================================================\n");

        $products = Product::with(['brand', 'identifiers', 'specifications', 'offers'])->get();

        if ($products->isEmpty()) {
            $this->warn("No canonical products in database to score.");
            return Command::SUCCESS;
        }

        $scores = [];
        $gradeDistribution = [
            'Excellent (90-100)' => 0,
            'Good (75-89)' => 0,
            'Needs Improvement (60-74)' => 0,
            'Not Publishable (<60)' => 0,
        ];

        foreach ($products as $p) {
            $score = $qualityService->calculateQualityScore($p);
            $grade = $qualityService->getQualityGrade($score);

            if ($score >= 90) $gradeDistribution['Excellent (90-100)']++;
            elseif ($score >= 75) $gradeDistribution['Good (75-89)']++;
            elseif ($score >= 60) $gradeDistribution['Needs Improvement (60-74)']++;
            else $gradeDistribution['Not Publishable (<60)']++;

            $scores[] = [
                'id' => $p->id,
                'name' => substr($p->name, 0, 35),
                'score' => $score,
                'grade' => $grade,
                'status' => $p->status,
            ];
        }

        $avgScore = count($scores) > 0 ? round(collect($scores)->avg('score'), 1) : 0;

        $this->info("Total Products Analyzed: " . count($products));
        $this->info("Average Catalog Quality Score: {$avgScore} / 100\n");

        $distRows = [];
        foreach ($gradeDistribution as $tier => $count) {
            $pct = count($products) > 0 ? round(($count / count($products)) * 100, 1) : 0;
            $distRows[] = [$tier, $count, "{$pct}%"];
        }

        $this->table(['Quality Grade', 'Product Count', 'Percentage'], $distRows);

        if (count($scores) > 0) {
            $this->info("\nTop Scored Sample:");
            $this->table(['ID', 'Product Name', 'Score', 'Grade', 'Status'], array_slice($scores, 0, 10));
        }

        return Command::SUCCESS;
    }
}

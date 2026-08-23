<?php

namespace App\Console\Commands;

use App\Services\Automation\CpuSafeIngestionOrchestrator;
use Illuminate\Console\Command;

class RefreshStalePricesCommand extends Command
{
    protected $signature = 'automation:refresh-prices';
    protected $description = 'Run a bounded, CPU-safe batch price refresh for stale offers';

    public function handle(CpuSafeIngestionOrchestrator $orchestrator): int
    {
        $this->info('Starting CPU-safe price refresh batch...');
        $result = $orchestrator->runPriceRefreshBatch();

        $this->table(
            ['Job ID', 'Status', 'Processed', 'Failed', 'Peak RAM (MB)', 'CPU Time (s)'],
            [[
                $result['job_id'] ?? 'N/A',
                $result['status'],
                $result['processed'],
                $result['failed'],
                $result['memory_peak_mb'],
                $result['cpu_time_sec'],
            ]]
        );

        return Command::SUCCESS;
    }
}

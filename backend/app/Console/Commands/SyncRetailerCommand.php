<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncRetailerCommand extends Command
{
    protected $signature = 'automation:sync-retailer {retailer : Retailer slug or code} {--limit=10 : Max items} {--dry-run : Dry run mode}';
    protected $description = 'Automation wrapper to synchronize a specific retailer';

    public function handle(): int
    {
        return $this->call('affiliate:sync', [
            'retailer' => $this->argument('retailer'),
            '--limit' => (int) $this->option('limit'),
            '--dry-run' => (bool) $this->option('dry-run'),
        ]);
    }
}

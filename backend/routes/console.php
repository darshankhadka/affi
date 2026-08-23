<?php

use Illuminate\Support\Facades\Schedule;

// Bounded Provider Synchronizations (Every 15-30 minutes, CPU-Safe with Cache Locks)
Schedule::command('automation:ingest-provider --provider=cj --market=us --limit=25')
    ->everyFifteenMinutes()
    ->withoutOverlapping(240)
    ->runInBackground();

Schedule::command('automation:ingest-provider --provider=awin --market=uk --limit=25')
    ->everyThirtyMinutes()
    ->withoutOverlapping(240)
    ->runInBackground();

Schedule::command('automation:ingest-provider --provider=impact --market=us --limit=25')
    ->hourly()
    ->withoutOverlapping(240)
    ->runInBackground();

// Price Freshness & Stale Price Refresh (Hourly, Bounded to 25 items)
Schedule::command('automation:refresh-prices --batch=25')
    ->hourly()
    ->withoutOverlapping(180)
    ->runInBackground();

// Best Price Materialization & In-Stock Prioritization (Hourly)
Schedule::command('pricing:recalculate')
    ->hourly()
    ->withoutOverlapping(180)
    ->runInBackground();

// Data Quality & Catalog Integrity Audit (Daily)
Schedule::call(function () {
    app(\App\Services\Quality\DataQualityService::class)->audit();
})->daily()->at('03:00');

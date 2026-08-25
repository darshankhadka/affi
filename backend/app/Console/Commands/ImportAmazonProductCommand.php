<?php

namespace App\Console\Commands;

use App\Services\Affiliate\AmazonManualImportService;
use App\Support\SecretRedactor;
use Illuminate\Console\Command;

class ImportAmazonProductCommand extends Command
{
    protected $signature = 'affiliate:import-amazon
        {url : Legitimate Amazon product or affiliate URL}
        {--name= : Product title/name}
        {--price= : Offer price (numeric)}
        {--brand=Generic : Brand name}
        {--model= : Model number}
        {--market=us : Target market code (us, uk, de, fr, es, it, au, ca)}
        {--currency= : Currency code (USD, GBP, EUR, AUD, CAD)}
        {--category= : Category slug}
        {--image= : Product image URL}
        {--tag= : Custom associate tag (defaults to configured market tag)}
        {--upc= : UPC identifier}
        {--ean= : EAN identifier}
        {--mpn= : MPN identifier}';

    protected $description = 'Manually import or link an authorized Amazon product offer by URL/ASIN (Mode 1)';

    public function handle(AmazonManualImportService $importService): int
    {
        $url = (string) $this->argument('url');
        $this->info("=== ARIKARTECH — AMAZON MANUAL IMPORT (MODE 1) ===");

        $validation = $importService->validateAndParseUrl($url, $this->option('tag'));
        if (!$validation['valid']) {
            $this->error("Invalid Amazon URL: {$validation['error']}");
            return Command::FAILURE;
        }

        $this->table(['Field', 'Value'], [
            ['ASIN', $validation['asin']],
            ['Domain', $validation['domain']],
            ['Market', strtoupper($validation['market_code'])],
            ['Clean URL', $validation['clean_url']],
            ['Monetized URL', SecretRedactor::sanitizeUrl($validation['monetized_url'])],
            ['Associate Tag', $validation['associate_tag']],
            ['Existing Product Match', $validation['existing_product'] ? "#{$validation['existing_product']['id']} {$validation['existing_product']['name']} ({$validation['existing_product']['match_type']})" : 'None (New Product will be created)'],
        ]);

        $name = $this->option('name');
        if (empty($name)) {
            $name = $this->ask('Enter product title/name');
        }

        $price = $this->option('price');
        if (empty($price)) {
            $price = $this->ask('Enter offer price (e.g. 999.99)');
        }

        $brand = $this->option('brand') ?: 'Generic';
        $market = $this->option('market') ?: $validation['market_code'];

        $result = $importService->import([
            'url' => $url,
            'name' => $name,
            'price' => (float) $price,
            'brand_name' => $brand,
            'model_number' => $this->option('model'),
            'category_slug' => $this->option('category'),
            'market_code' => $market,
            'currency_code' => $this->option('currency'),
            'image_url' => $this->option('image'),
            'upc' => $this->option('upc'),
            'ean' => $this->option('ean'),
            'mpn' => $this->option('mpn'),
            'associate_tag' => $this->option('tag'),
        ]);

        if (!$result['success']) {
            $this->error("Import failed: {$result['error']}");
            return Command::FAILURE;
        }

        $this->info("\n✔ Successfully imported Amazon offer!");
        $this->table(['Result', 'Details'], [
            ['Action', $result['action']],
            ['Product ID', $result['product']?->id],
            ['Product Name', $result['product']?->name],
            ['Product Slug', $result['product']?->slug],
            ['Offer ID', $result['offer']?->id],
            ['Offer Price', "{$result['offer']?->price} {$result['offer']?->currency?->code}"],
            ['Retailer', 'Amazon'],
            ['Affiliate URL', SecretRedactor::sanitizeUrl($result['offer']?->affiliate_url)],
        ]);

        return Command::SUCCESS;
    }
}

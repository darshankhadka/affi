<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Models\Retailer;
use App\Services\Affiliate\AffiliateRegistry;
use Illuminate\Console\Command;

class AffiliateTestRetailerCommand extends Command
{
    protected $signature = 'affiliate:test-retailer {retailer : Retailer slug or code (e.g. amazon-us, currys-uk, mediamarkt-de)}';
    protected $description = 'Test retailer configuration, market linkage, provider assignment, and deep link generation';

    public function handle(AffiliateRegistry $registry): int
    {
        $identifier = $this->argument('retailer');
        $retailer = Retailer::where('slug', $identifier)
            ->orWhere('code', $identifier)
            ->with(['affiliateProvider', 'market'])
            ->first();

        if (!$retailer) {
            $this->error("Retailer [{$identifier}] not found in database.");
            return Command::FAILURE;
        }

        $this->info("Inspecting Retailer: {$retailer->name} ({$retailer->slug})");

        $provider = $retailer->affiliateProvider;
        $providerInstance = $provider && $registry->has($provider->code) ? $registry->get($provider->code) : null;
        $providerStatus = $provider ? $provider->status : 'no_provider';

        $offersCount = Offer::where('retailer_id', $retailer->id)->count();
        $sampleOffer = Offer::where('retailer_id', $retailer->id)->first();

        $generatedUrl = 'N/A';
        if ($sampleOffer && $retailer->market && $providerInstance) {
            $generatedUrl = $providerInstance->generateAffiliateUrl($sampleOffer, $retailer->market, 'test_subid');
        }

        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $retailer->id],
                ['Name', $retailer->name],
                ['Slug', $retailer->slug],
                ['Domain', $retailer->domain],
                ['Country', $retailer->country ?? '—'],
                ['Market', $retailer->market_code ?? '—'],
                ['Currency', $retailer->currency_code ?? '—'],
                ['Provider', $provider ? "{$provider->name} ({$provider->code})" : 'None'],
                ['Provider Status', strtoupper($providerStatus)],
                ['Retailer Status', strtoupper($retailer->status ?? 'not_configured')],
                ['Integration Type', $retailer->integration_type ?? 'affiliate_network'],
                ['API Available', $retailer->api_available ? 'YES' : 'NO'],
                ['Feed Available', $retailer->feed_available ? 'YES' : 'NO'],
                ['Active Offers in DB', $offersCount],
                ['Sample Deep Link', $generatedUrl],
                ['Last Sync', $retailer->last_successful_sync_at?->toIso8601String() ?? 'Never'],
                ['Last Error', $retailer->last_error ?? 'None'],
            ]
        );

        $this->info("✔ Retailer audit completed for [{$retailer->slug}].");
        return Command::SUCCESS;
    }
}

<?php

namespace Tests\Feature;

use App\DTOs\RetailerIdentityInput;
use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Models\Retailer;
use App\Services\Ingestion\RetailerIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetailerIdentityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('system:init-foundation');
        $this->provider = AffiliateProvider::firstOrCreate(
            ['code' => 'awin'],
            ['name' => 'Awin', 'is_active' => true, 'status' => 'connected', 'type' => 'datafeed']
        );
    }

    public function test_advertiser_id_gives_stable_code_and_deduplicates(): void
    {
        $service = new RetailerIdentityService();

        $r1 = $service->resolveOrCreate(new RetailerIdentityInput(
            providerId: $this->provider->id,
            advertiserId: '25962',
            providerCode: 'awin',
            name: 'BlazeVideo DE',
            domain: 'blazevideos.de',
            marketCode: 'de',
            currencyCode: 'EUR',
        ));

        $this->assertEquals('awin-25962', $r1->code);
        $this->assertEquals('25962', $r1->affiliate_program_id);

        // Same advertiser id but churned name/domain formatting -> same retailer.
        $r2 = $service->resolveOrCreate(new RetailerIdentityInput(
            providerId: $this->provider->id,
            advertiserId: '25962',
            providerCode: 'awin',
            name: 'BlazeVideo Deutschland',
            domain: 'www.blazevideos.de',
            marketCode: 'de',
            currencyCode: 'EUR',
        ));

        $this->assertEquals($r1->id, $r2->id);
        $this->assertEquals('awin-25962', $r2->code);
    }

    public function test_domain_subdomain_variants_map_to_same_canonical_retailer(): void
    {
        $service = new RetailerIdentityService();

        $a = $service->resolveOrCreate(new RetailerIdentityInput(
            providerId: null,
            advertiserId: null,
            providerCode: 'cj',
            name: 'Example Shop',
            domain: 'shop.example.com',
            marketCode: 'us',
        ));
        $b = $service->resolveOrCreate(new RetailerIdentityInput(
            providerId: null,
            advertiserId: null,
            providerCode: 'cj',
            name: 'Example Shop US',
            domain: 'www.example.com',
            marketCode: 'us',
        ));

        $this->assertEquals($a->id, $b->id);
        $this->assertEquals('example.com', $b->canonical_domain);
    }

    public function test_same_advertiser_resolves_to_one_canonical_retailer_across_markets(): void
    {
        $service = new RetailerIdentityService();

        // A single Awin advertiser is ONE canonical merchant; market is captured per-offer.
        $de = $service->resolveOrCreate(new RetailerIdentityInput(
            providerId: $this->provider->id,
            advertiserId: '25962',
            providerCode: 'awin',
            name: 'BlazeVideo DE',
            domain: 'blazevideos.de',
            marketCode: 'de',
        ));
        $at = $service->resolveOrCreate(new RetailerIdentityInput(
            providerId: $this->provider->id,
            advertiserId: '25962',
            providerCode: 'awin',
            name: 'BlazeVideo AT',
            domain: 'blazevideos.de',
            marketCode: 'at',
        ));

        $this->assertEquals($de->id, $at->id);
        $this->assertEquals('25962', $at->affiliate_program_id);
    }

    public function test_distinct_advertisers_sharing_root_domain_are_not_merged(): void
    {
        $service = new RetailerIdentityService();

        $a = $service->resolveOrCreate(new RetailerIdentityInput(
            providerId: $this->provider->id,
            advertiserId: '25962',
            providerCode: 'awin',
            name: 'BlazeVideo DE',
            domain: 'blazevideos.de',
            marketCode: 'de',
        ));
        $b = $service->resolveOrCreate(new RetailerIdentityInput(
            providerId: $this->provider->id,
            advertiserId: '57897',
            providerCode: 'awin',
            name: 'Geekbuying DE',
            domain: 'geekbuying.de',
            marketCode: 'de',
        ));

        // Distinct advertisers must never collapse into one retailer.
        $this->assertNotEquals($a->id, $b->id);
        $this->assertEquals('25962', $a->affiliate_program_id);
        $this->assertEquals('57897', $b->affiliate_program_id);
    }

    public function test_canonical_domain_normalization(): void
    {
        $service = new RetailerIdentityService();
        $this->assertEquals('example.de', $service->canonicalDomain('https://www.example.de/path?x=1'));
        $this->assertEquals('example.co.uk', $service->canonicalDomain('shop.example.co.uk'));
        $this->assertEquals('example.com', $service->canonicalDomain('EXAMPLE.COM'));
        $this->assertNull($service->canonicalDomain(''));
    }
}

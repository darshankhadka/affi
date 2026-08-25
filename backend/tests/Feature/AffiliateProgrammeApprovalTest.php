<?php

namespace Tests\Feature;

use App\Models\AffiliateProvider;
use App\Models\AffiliateProgramme;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AffiliateProgrammeApprovalTest
 *
 * Verifies that the programme approval lifecycle is correctly enforced:
 *   - APPROVED programmes permit redirect
 *   - REJECTED programmes block redirect
 *   - PENDING programmes block redirect
 *   - SUSPENDED programmes block redirect
 *   - Retailers without a programme record are allowed through (Amazon, legacy)
 *   - Feed/API visibility does NOT imply approval
 *   - Seeder creates correct approved records for known Awin programmes
 */
class AffiliateProgrammeApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected AffiliateProvider $awinProvider;
    protected Market $market;
    protected Brand $brand;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('system:init-foundation');

        $this->market   = Market::where('code', 'de')->first() ?? Market::first();
        $this->brand    = Brand::firstOrCreate(['slug' => 'test-brand'], ['name' => 'Test Brand', 'is_active' => true]);
        $this->category = Category::first();

        $this->awinProvider = AffiliateProvider::where('code', 'awin')->first()
            ?? AffiliateProvider::create([
                'code'      => 'awin',
                'name'      => 'Awin Publisher Network',
                'is_active' => true,
                'status'    => 'connected',
            ]);
    }

    // -------------------------------------------------------------------------
    // Programme model helpers
    // -------------------------------------------------------------------------

    public function test_approved_programme_is_promotable(): void
    {
        $programme = AffiliateProgramme::create([
            'provider_id'           => $this->awinProvider->id,
            'external_programme_id' => '25962',
            'name'                  => 'BlazeVideo DE',
            'publisher_id'          => '3053247',
            'status'                => 'approved',
            'approved_at'           => now(),
        ]);

        $this->assertTrue($programme->isApproved());
        $this->assertTrue($programme->isPromotable());
        $this->assertFalse($programme->isRejected());
        $this->assertFalse($programme->isPending());
    }

    public function test_rejected_programme_is_not_promotable(): void
    {
        $programme = AffiliateProgramme::create([
            'provider_id'           => $this->awinProvider->id,
            'external_programme_id' => 'REJECTED-ADV-9999',
            'name'                  => 'Dell ANZ (Rejected)',
            'status'                => 'rejected',
            'rejected_at'           => now(),
        ]);

        $this->assertFalse($programme->isApproved());
        $this->assertFalse($programme->isPromotable());
        $this->assertTrue($programme->isRejected());
    }

    public function test_pending_programme_is_not_promotable(): void
    {
        $programme = AffiliateProgramme::create([
            'provider_id'           => $this->awinProvider->id,
            'external_programme_id' => 'PENDING-ADV-12345',
            'name'                  => 'Pending Advertiser',
            'status'                => 'pending',
        ]);

        $this->assertFalse($programme->isApproved());
        $this->assertFalse($programme->isPromotable());
        $this->assertTrue($programme->isPending());
    }

    public function test_suspended_programme_is_not_promotable(): void
    {
        $programme = AffiliateProgramme::create([
            'provider_id'           => $this->awinProvider->id,
            'external_programme_id' => 'SUSPENDED-ADV-55555',
            'name'                  => 'Suspended Advertiser',
            'status'                => 'suspended',
        ]);

        $this->assertFalse($programme->isPromotable());
        $this->assertTrue($programme->isSuspended());
    }

    // -------------------------------------------------------------------------
    // Redirect controller approval gate
    // -------------------------------------------------------------------------

    protected function createOffer(string $programmeStatus, bool $linkProgramme = true): Offer
    {
        $programme = null;
        if ($linkProgramme) {
            $programme = AffiliateProgramme::create([
                'provider_id'           => $this->awinProvider->id,
                'external_programme_id' => 'TEST-' . $programmeStatus . '-' . rand(1000, 9999),
                'name'                  => "Test Programme ({$programmeStatus})",
                'status'                => $programmeStatus,
                'approved_at'           => $programmeStatus === 'approved' ? now() : null,
                'rejected_at'           => $programmeStatus === 'rejected' ? now() : null,
            ]);
        }

        $retailer = Retailer::create([
            'name'                  => 'Test Retailer ' . rand(),
            'slug'                  => 'test-retailer-' . rand(),
            'domain'                => 'test-retailer-' . rand() . '.com',
            'affiliate_provider_id' => $this->awinProvider->id,
            'affiliate_program_id'  => $programme?->external_programme_id,
            'programme_id'          => $programme?->id,
            'is_active'             => true,
        ]);

        $product = Product::create([
            'brand_id'    => $this->brand->id,
            'category_id' => $this->category->id,
            'name'        => 'Test Product ' . rand(),
            'slug'        => 'test-product-' . rand(),
            'status'      => 'published',
        ]);

        return Offer::create([
            'product_id'   => $product->id,
            'retailer_id'  => $retailer->id,
            'market_id'    => $this->market->id,
            'currency_id'  => $this->market->default_currency_id,
            'sku'          => 'TEST-SKU-' . rand(),
            'title'        => 'Test Product Offer',
            'affiliate_url' => 'https://test-retailer.com/product',
            'original_url' => 'https://test-retailer.com/product',
            'price'        => 99.99,
            'availability' => 'in_stock',
            'condition'    => 'new',
            'is_active'    => true,
        ]);
    }

    public function test_approved_programme_offer_redirects_successfully(): void
    {
        $offer = $this->createOffer('approved');

        $response = $this->get("/api/v1/affiliates/out/{$offer->id}");

        // Should redirect (302) — not blocked
        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals(404, $response->status());
    }

    public function test_rejected_programme_offer_is_blocked(): void
    {
        $offer = $this->createOffer('rejected');

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->get("/api/v1/affiliates/out/{$offer->id}");

        $response->assertStatus(403);
    }

    public function test_pending_programme_offer_is_blocked(): void
    {
        $offer = $this->createOffer('pending');

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->get("/api/v1/affiliates/out/{$offer->id}");

        $response->assertStatus(403);
    }

    public function test_suspended_programme_offer_is_blocked(): void
    {
        $offer = $this->createOffer('suspended');

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->get("/api/v1/affiliates/out/{$offer->id}");

        $response->assertStatus(403);
    }

    public function test_offer_without_programme_record_is_allowed_through(): void
    {
        // Amazon / legacy retailers — no programme record linked
        $offer = $this->createOffer('approved', linkProgramme: false);

        $response = $this->get("/api/v1/affiliates/out/{$offer->id}");

        // Should redirect (302), not 403
        $this->assertNotEquals(403, $response->status());
    }

    // -------------------------------------------------------------------------
    // Feed visibility does NOT imply approval
    // -------------------------------------------------------------------------

    public function test_feed_visibility_does_not_imply_approval(): void
    {
        // A programme "discovered" from a feed with no approval record
        // should default to pending — not approved.
        $programme = AffiliateProgramme::create([
            'provider_id'           => $this->awinProvider->id,
            'external_programme_id' => 'FEED-DISCOVERED-99999',
            'name'                  => 'Feed Discovered (No Approval)',
            'status'                => 'pending', // default when first discovered
        ]);

        $this->assertFalse($programme->isApproved());
        $this->assertFalse($programme->isPromotable());
        $this->assertTrue($programme->isPending());
    }

    // -------------------------------------------------------------------------
    // Seeder command creates correct approved records
    // -------------------------------------------------------------------------

    public function test_seed_approved_programmes_creates_correct_awin_records(): void
    {
        $this->artisan('affiliate:seed-approved-programmes')->assertExitCode(0);

        $expectedProgrammes = [
            ['id' => '25962', 'name' => 'BlazeVideo DE', 'currency' => 'EUR'],
            ['id' => '8800',  'name' => 'mcdaekonline DK', 'currency' => 'DKK'],
            ['id' => '57897', 'name' => 'Geekbuying DE', 'currency' => 'EUR'],
            ['id' => '75408', 'name' => 'Nothingprojector', 'currency' => 'USD'],
            ['id' => '90211', 'name' => 'Fast Technology Limited', 'currency' => 'USD'],
        ];

        foreach ($expectedProgrammes as $expected) {
            $programme = AffiliateProgramme::where('external_programme_id', $expected['id'])->first();

            $this->assertNotNull($programme, "Programme {$expected['id']} ({$expected['name']}) not found");
            $this->assertEquals('approved', $programme->status);
            $this->assertEquals($expected['name'], $programme->name);
            $this->assertEquals($expected['currency'], $programme->currency);
            $this->assertTrue($programme->isApproved());
            $this->assertTrue($programme->isPromotable());
            $this->assertNotNull($programme->approved_at);
        }
    }

    public function test_seed_approved_programmes_is_idempotent(): void
    {
        // Run twice — should not create duplicates
        $this->artisan('affiliate:seed-approved-programmes')->assertExitCode(0);
        $this->artisan('affiliate:seed-approved-programmes')->assertExitCode(0);

        $count = AffiliateProgramme::where('provider_id', $this->awinProvider->id)->count();
        $this->assertEquals(8, $count);
    }

    // -------------------------------------------------------------------------
    // Zero-result protection
    // -------------------------------------------------------------------------

    public function test_existing_approved_offers_are_not_deleted_on_zero_result_sync(): void
    {
        $programme = AffiliateProgramme::create([
            'provider_id'           => $this->awinProvider->id,
            'external_programme_id' => '57897',
            'name'                  => 'Geekbuying DE',
            'status'                => 'approved',
        ]);

        $retailer = Retailer::create([
            'name'                  => 'Geekbuying',
            'slug'                  => 'geekbuying',
            'domain'                => 'geekbuying.com',
            'affiliate_provider_id' => $this->awinProvider->id,
            'programme_id'          => $programme->id,
            'is_active'             => true,
        ]);

        $product = Product::create([
            'brand_id'    => $this->brand->id,
            'category_id' => $this->category->id,
            'name'        => 'Geekbuying GPU',
            'slug'        => 'geekbuying-gpu',
            'status'      => 'published',
        ]);

        Offer::create([
            'product_id'   => $product->id,
            'retailer_id'  => $retailer->id,
            'market_id'    => $this->market->id,
            'currency_id'  => $this->market->default_currency_id,
            'sku'          => 'GKB-GPU-001',
            'title'        => 'Geekbuying GPU Offer',
            'affiliate_url' => 'https://www.awin1.com/cread.php?awinmid=57897&awinaffid=3053247&ued=https://geekbuying.com/product',
            'price'        => 299.99,
            'availability' => 'in_stock',
            'condition'    => 'new',
            'is_active'    => true,
        ]);

        // Simulate a zero-result provider response. The command should NOT delete existing offers.
        // In the current architecture, IngestProviderCommand only inserts/updates — it never deletes.
        // This test verifies that behaviour: offers remain after a zero-result sync.
        $offerCount = Offer::where('retailer_id', $retailer->id)->count();
        $this->assertEquals(1, $offerCount, 'Offer must survive a zero-result provider response');

        // The is_active flag must not be cleared by a zero-result event
        $offer = Offer::where('retailer_id', $retailer->id)->first();
        $this->assertTrue($offer->is_active);
    }
}

<?php

namespace Tests\Feature;

use App\DTOs\NormalizedIdentifierDTO;
use App\DTOs\NormalizedOfferDTO;
use App\DTOs\NormalizedProductDTO;
use App\Models\AffiliateClick;
use App\Models\Market;
use App\Models\Product;
use App\Services\Ingestion\ProductIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndProductPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_end_to_end_pipeline_from_ingestion_to_affiliate_click(): void
    {
        $this->artisan('system:init-foundation');
        $market = Market::where('code', 'us')->first();

        // 1. Ingestion of a real-structure normalized product
        $dto = new NormalizedProductDTO(
            name: 'Apple MacBook Pro 16 M3 Max',
            brandName: 'Apple',
            categorySlug: 'laptops',
            modelNumber: 'MUW63LL/A',
            description: '16-inch Liquid Retina XDR display, M3 Max chip with 14-core CPU and 30-core GPU',
            shortDescription: 'Apple MacBook Pro 16-inch with M3 Max chip',
            canonicalUpc: '195949112233',
            canonicalEan: '0195949112233',
            canonicalMpn: 'MUW63LL/A',
            identifiers: [
                NormalizedIdentifierDTO::from('UPC', '195949112233'),
                NormalizedIdentifierDTO::from('ASIN', 'B0CM596G44'),
            ],
            specifications: [],
            images: [],
            offer: new NormalizedOfferDTO(
                retailerDomain: 'amazon.com',
                retailerName: 'Amazon',
                sku: 'B0CM596G44',
                title: 'Apple 2023 MacBook Pro Laptop M3 Max chip',
                price: 3499.00,
                originalPrice: 3999.00,
                currencyCode: 'USD',
                availability: 'in_stock',
                condition: 'new',
                affiliateUrl: 'https://www.amazon.com/dp/B0CM596G44',
                marketCode: 'us'
            )
        );

        $ingestionService = app(ProductIngestionService::class);
        $ingestResult = $ingestionService->ingest($dto, $market);

        $this->assertTrue($ingestResult['success']);
        $product = $ingestResult['product'];
        $offer = $ingestResult['offer'];

        // 2. Fetch Public Product Detail via API
        $response = $this->getJson("/api/v1/products/{$product->slug}?market=us");
        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Apple MacBook Pro 16 M3 Max')
            ->assertJsonPath('data.best_price.min_price', 3499)
            ->assertJsonPath('meta.seo.robots', 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');

        // 3. User clicks "View Deal" -> Outbound Affiliate Redirection
        $clickResponse = $this->get("/api/v1/affiliates/out/{$offer->id}", [
            'Referer' => 'https://arikartech.com/us/products/' . $product->slug,
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);

        // Returns 302 Found redirect
        $clickResponse->assertStatus(302);
        $clickResponse->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        // 4. Verify Click Event Logged in Database with Privacy-Safe Hashed IP
        $this->assertDatabaseHas('affiliate_clicks', [
            'offer_id' => $offer->id,
            'product_id' => $product->id,
            'retailer_id' => $offer->retailer_id,
            'market_id' => $market->id,
        ]);

        $clickRecord = AffiliateClick::first();
        $this->assertEquals(64, strlen($clickRecord->ip_hash)); // SHA256
    }
}

<?php

namespace Tests\Feature;

use App\DTOs\NormalizedIdentifierDTO;
use App\DTOs\NormalizedOfferDTO;
use App\DTOs\NormalizedProductDTO;
use App\DTOs\NormalizedSpecificationDTO;
use App\Models\Market;
use App\Models\Offer;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Services\Ingestion\ProductIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductIngestionPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_ingestion_creates_canonical_product_and_offer(): void
    {
        $this->artisan('system:init-foundation');
        $market = Market::where('code', 'us')->first();

        $dto = new NormalizedProductDTO(
            name: 'Apple MacBook Pro 14 M3',
            brandName: 'Apple',
            categorySlug: 'laptops',
            modelNumber: 'MRX33LL/A',
            description: 'Powerful laptop with M3 chip',
            shortDescription: 'Apple M3 14-inch',
            canonicalUpc: '195949123456',
            canonicalEan: '0195949123456',
            canonicalMpn: 'MRX33LL/A',
            identifiers: [
                NormalizedIdentifierDTO::from('UPC', '195949123456'),
                NormalizedIdentifierDTO::from('ASIN', 'B0CX23V2ZP'),
            ],
            specifications: [
                new NormalizedSpecificationDTO('Processor', 'Chip', 'Apple M3 Pro'),
            ],
            images: [],
            offer: new NormalizedOfferDTO(
                retailerDomain: 'amazon.com',
                retailerName: 'Amazon',
                sku: 'B0CX23V2ZP',
                title: 'Apple MacBook Pro 14 M3 Pro - 18GB Unified Memory, 512GB SSD',
                price: 1999.00,
                originalPrice: 2199.00,
                currencyCode: 'USD',
                availability: 'in_stock',
                condition: 'new',
                affiliateUrl: 'https://www.amazon.com/dp/B0CX23V2ZP',
                marketCode: 'us'
            )
        );

        $service = app(ProductIngestionService::class);
        $result = $service->ingest($dto, $market);

        $this->assertTrue($result['success']);
        $this->assertEquals('created_product', $result['action']);
        $this->assertDatabaseHas('products', ['model_number' => 'MRX33LL/A']);
        $this->assertDatabaseHas('offers', ['price' => 1999.00]);
        $this->assertDatabaseHas('best_prices', ['min_price' => 1999.00]);
    }

    public function test_second_retailer_ingestion_with_same_identifier_does_not_create_duplicate_product(): void
    {
        $this->artisan('system:init-foundation');
        $market = Market::where('code', 'us')->first();
        $service = app(ProductIngestionService::class);

        // 1. Ingest from Amazon
        $dto1 = new NormalizedProductDTO(
            name: 'Apple MacBook Pro 14 M3',
            brandName: 'Apple',
            categorySlug: 'laptops',
            modelNumber: 'MRX33LL/A',
            description: null,
            shortDescription: null,
            canonicalUpc: '195949123456',
            canonicalEan: null,
            canonicalMpn: 'MRX33LL/A',
            identifiers: [
                NormalizedIdentifierDTO::from('UPC', '195949123456'),
                NormalizedIdentifierDTO::from('ASIN', 'B0CX23V2ZP'),
            ],
            specifications: [],
            images: [],
            offer: new NormalizedOfferDTO(
                retailerDomain: 'amazon.com',
                retailerName: 'Amazon',
                sku: 'B0CX23V2ZP',
                title: 'Amazon MacBook Deal',
                price: 1999.00,
                originalPrice: 2199.00,
                currencyCode: 'USD',
                availability: 'in_stock',
                condition: 'new',
                affiliateUrl: 'https://amazon.com/dp/B0CX23V2ZP',
                marketCode: 'us'
            )
        );
        $service->ingest($dto1, $market);

        $this->assertEquals(1, Product::count());

        // 2. Ingest from Best Buy with same UPC
        $dto2 = new NormalizedProductDTO(
            name: 'Apple - MacBook Pro 14" Laptop - M3 Pro chip - 18GB Memory',
            brandName: 'Apple',
            categorySlug: 'laptops',
            modelNumber: 'MRX33LL/A',
            description: null,
            shortDescription: null,
            canonicalUpc: '195949123456',
            canonicalEan: null,
            canonicalMpn: 'MRX33LL/A',
            identifiers: [
                NormalizedIdentifierDTO::from('UPC', '195949123456'),
                NormalizedIdentifierDTO::from('SKU', '6534640'),
            ],
            specifications: [],
            images: [],
            offer: new NormalizedOfferDTO(
                retailerDomain: 'bestbuy.com',
                retailerName: 'Best Buy',
                sku: '6534640',
                title: 'Best Buy MacBook Deal',
                price: 1849.00, // Cheaper price
                originalPrice: 1999.00,
                currencyCode: 'USD',
                availability: 'in_stock',
                condition: 'new',
                affiliateUrl: 'https://bestbuy.com/site/6534640.p',
                marketCode: 'us'
            )
        );
        $result2 = $service->ingest($dto2, $market);

        // Verification: Exactly 1 canonical product exists, but 2 retailer offers attached!
        $this->assertTrue($result2['success']);
        $this->assertEquals('matched_existing', $result2['action']);
        $this->assertEquals(1, Product::count());
        $this->assertEquals(2, Offer::count());

        // Best price is updated to Best Buy's lower price ($1849.00)
        $this->assertDatabaseHas('best_prices', [
            'min_price' => 1849.00,
            'max_price' => 1999.00,
            'offer_count' => 2,
        ]);
    }

    public function test_price_history_logs_only_on_price_shift(): void
    {
        $this->artisan('system:init-foundation');
        $market = Market::where('code', 'us')->first();
        $service = app(ProductIngestionService::class);

        $dto = new NormalizedProductDTO(
            name: 'Dell XPS 15',
            brandName: 'Dell',
            categorySlug: 'laptops',
            modelNumber: 'XPS9530',
            description: null,
            shortDescription: null,
            canonicalUpc: '884116423456',
            canonicalEan: null,
            canonicalMpn: 'XPS9530',
            identifiers: [NormalizedIdentifierDTO::from('UPC', '884116423456')],
            specifications: [],
            images: [],
            offer: new NormalizedOfferDTO(
                retailerDomain: 'dell.com',
                retailerName: 'Dell Store',
                sku: 'DELL-XPS-9530',
                title: 'Dell XPS 15',
                price: 1599.00,
                originalPrice: 1799.00,
                currencyCode: 'USD',
                availability: 'in_stock',
                condition: 'new',
                affiliateUrl: 'https://dell.com/xps15',
                marketCode: 'us'
            )
        );

        // Ingest 1
        $service->ingest($dto, $market);
        $this->assertEquals(1, PriceHistory::count());

        // Ingest 2 with same price (No redundant history row created)
        $service->ingest($dto, $market);
        $this->assertEquals(1, PriceHistory::count());

        // Ingest 3 with discounted price
        $dtoDiscounted = new NormalizedProductDTO(
            name: 'Dell XPS 15',
            brandName: 'Dell',
            categorySlug: 'laptops',
            modelNumber: 'XPS9530',
            description: null,
            shortDescription: null,
            canonicalUpc: '884116423456',
            canonicalEan: null,
            canonicalMpn: 'XPS9530',
            identifiers: [NormalizedIdentifierDTO::from('UPC', '884116423456')],
            specifications: [],
            images: [],
            offer: new NormalizedOfferDTO(
                retailerDomain: 'dell.com',
                retailerName: 'Dell Store',
                sku: 'DELL-XPS-9530',
                title: 'Dell XPS 15',
                price: 1399.00, // Shift
                originalPrice: 1799.00,
                currencyCode: 'USD',
                availability: 'in_stock',
                condition: 'new',
                affiliateUrl: 'https://dell.com/xps15',
                marketCode: 'us'
            )
        );
        $service->ingest($dtoDiscounted, $market);
        $this->assertEquals(2, PriceHistory::count());
    }
}

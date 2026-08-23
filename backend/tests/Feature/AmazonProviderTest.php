<?php

namespace Tests\Feature;

use App\DTOs\NormalizedIdentifierDTO;
use App\Models\AffiliateAccount;
use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Retailer;
use App\Services\Affiliate\AmazonProvider;
use App\Services\Affiliate\AmazonSigV4Signer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AmazonProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_sigv4_signer_generates_valid_headers(): void
    {
        $signer = new AmazonSigV4Signer();
        $payload = json_encode(['ItemIds' => ['B0CX23V2ZP']]);
        $headers = $signer->sign(
            'AKIAIOSFODNN7EXAMPLE',
            'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'us-east-1',
            'webservices.amazon.com',
            'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems',
            $payload,
            '20260823T120000Z'
        );

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertArrayHasKey('x-amz-date', $headers);
        $this->assertArrayHasKey('x-amz-target', $headers);
        $this->assertStringContainsString('AWS4-HMAC-SHA256 Credential=AKIAIOSFODNN7EXAMPLE/20260823/us-east-1/ProductAdvertisingAPI/aws4_request', $headers['Authorization']);
    }

    public function test_test_connection_reports_not_configured_when_empty(): void
    {
        $provider = AffiliateProvider::create([
            'code' => 'amazon',
            'name' => 'Amazon Associates',
            'is_active' => false,
            'config' => null,
            'status' => 'disconnected',
        ]);

        $connector = new AmazonProvider();
        $result = $connector->testConnection($provider);

        $this->assertFalse($result['connected']);
        $this->assertEquals('not_configured', $result['status']);
    }

    public function test_test_connection_succeeds_when_paapi_responds(): void
    {
        Http::fake([
            'https://webservices.amazon.com/paapi5/getitems' => Http::response([
                'ItemsResult' => [
                    'Items' => [
                        ['ASIN' => 'B0CX23V2ZP', 'ItemInfo' => ['Title' => ['DisplayValue' => 'MacBook Pro 14']]]
                    ]
                ]
            ], 200),
        ]);

        $provider = AffiliateProvider::create([
            'code' => 'amazon',
            'name' => 'Amazon Associates',
            'is_active' => true,
            'config' => [
                'access_key' => 'AKIAFAKEKEY',
                'secret_key' => 'FAKESECRETKEY12345',
            ],
            'status' => 'disconnected',
        ]);

        $connector = new AmazonProvider();
        $result = $connector->testConnection($provider);

        $this->assertTrue($result['connected']);
        $this->assertEquals('connected', $result['status']);
    }

    public function test_generate_affiliate_url_injects_market_tag_and_subid(): void
    {
        $this->artisan('system:init-foundation');

        $market = Market::where('code', 'us')->first();
        $provider = AffiliateProvider::where('code', 'amazon')->first();
        $retailer = Retailer::create([
            'name' => 'Amazon US',
            'slug' => 'amazon-us',
            'domain' => 'amazon.com',
            'affiliate_provider_id' => $provider->id,
            'is_active' => true,
        ]);

        AffiliateAccount::create([
            'provider_id' => $provider->id,
            'market_id' => $market->id,
            'account_tag' => 'custom-tag-20',
            'is_active' => true,
        ]);

        $brand = \App\Models\Brand::firstOrCreate(['slug' => 'apple'], ['name' => 'Apple', 'is_active' => true]);
        $category = \App\Models\Category::first();
        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Apple Test Product',
            'slug' => 'apple-test-product',
            'status' => 'published',
        ]);

        $offer = Offer::create([
            'product_id' => $product->id,
            'retailer_id' => $retailer->id,
            'market_id' => $market->id,
            'currency_id' => $market->default_currency_id,
            'sku' => 'B0CX23V2ZP',
            'title' => 'Test Item',
            'affiliate_url' => 'https://www.amazon.com/dp/B0CX23V2ZP',
            'price' => 1999.00,
            'availability' => 'in_stock',
            'condition' => 'new',
            'is_active' => true,
        ]);

        $connector = new AmazonProvider();
        $url = $connector->generateAffiliateUrl($offer, $market, 'click123');

        $this->assertStringContainsString('tag=custom-tag-20', $url);
        $this->assertStringContainsString('ascsubtag=click123', $url);
    }
}

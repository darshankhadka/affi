<?php

namespace Tests\Feature;

use App\DTOs\NormalizedIdentifierDTO;
use App\DTOs\NormalizedProductDTO;
use App\Services\Normalization\ProductNormalizer;
use Tests\TestCase;

class ProductNormalizationTest extends TestCase
{
    public function test_normalizer_cleans_brand_names(): void
    {
        $normalizer = new ProductNormalizer();

        $this->assertEquals('Apple', $normalizer->normalizeBrand('Apple Computer Inc.'));
        $this->assertEquals('Apple', $normalizer->normalizeBrand('apple inc'));
        $this->assertEquals('NVIDIA', $normalizer->normalizeBrand('nvidia corp'));
        $this->assertEquals('Asus', $normalizer->normalizeBrand('ASUSTeK Computer Inc.'));
        $this->assertEquals('Corsair', $normalizer->normalizeBrand('Corsair Gaming'));
    }

    public function test_normalizer_cleans_model_numbers(): void
    {
        $normalizer = new ProductNormalizer();

        $this->assertEquals('MRX33LL/A', $normalizer->normalizeModel(' Model: MRX33LL/A '));
        $this->assertEquals('RTX 4090', $normalizer->normalizeModel('RTX 4090,'));
    }

    public function test_normalizer_strips_marketing_noise_from_titles(): void
    {
        $normalizer = new ProductNormalizer();

        $title = 'Apple MacBook Pro 14-inch M3 Pro - Free Shipping | Limited Time Deal [Newest Version]';
        $cleaned = $normalizer->normalizeTitle($title, 'Apple');

        $this->assertEquals('Apple MacBook Pro 14-inch M3 Pro', $cleaned);
    }

    public function test_normalizer_validates_and_pads_identifiers(): void
    {
        $normalizer = new ProductNormalizer();

        // 11 digit UPC padded to 12
        $upc = $normalizer->normalizeIdentifier('UPC', '19594912345');
        $this->assertNotNull($upc);
        $this->assertEquals('019594912345', $upc->normalizedValue);

        // Valid ASIN
        $asin = $normalizer->normalizeIdentifier('ASIN', 'b0cx23v2zp');
        $this->assertNotNull($asin);
        $this->assertEquals('B0CX23V2ZP', $asin->normalizedValue);

        // Invalid ASIN rejected
        $invalidAsin = $normalizer->normalizeIdentifier('ASIN', 'SHORT');
        $this->assertNull($invalidAsin);
    }
}

<?php

namespace App\Services\Matching;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductIdentifier;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

class ProductMatchingService
{
    /**
     * Match an incoming offer/product payload to an existing canonical product.
     * Uses prioritized indexed identifiers: UPC -> EAN -> GTIN -> ASIN -> MPN + Brand -> Model + Brand.
     *
     * @param array{
     *   identifiers?: array<string, string>, // ['UPC' => '...', 'EAN' => '...', 'ASIN' => '...', 'MPN' => '...']
     *   brand_name?: ?string,
     *   model_number?: ?string,
     *   name?: string,
     *   sku?: ?string
     * } $payload
     *
     * @return array{
     *   product: ?Product,
     *   variant: ?ProductVariant,
     *   match_type: ?string, // 'identifier:UPC', 'identifier:EAN', 'identifier:ASIN', 'brand_model', null
     *   confidence: float
     * }
     */
    public function match(array $payload): array
    {
        $identifiers = $payload['identifiers'] ?? [];

        // 1. High Confidence Indexed Identifiers Lookup
        $priorityTypes = ['UPC', 'EAN', 'GTIN', 'ASIN', 'MPN'];
        foreach ($priorityTypes as $type) {
            if (!empty($identifiers[$type])) {
                $normalized = ProductIdentifier::normalize($type, $identifiers[$type]);
                $record = ProductIdentifier::with(['product', 'variant'])
                    ->where('type', $type)
                    ->where('normalized_value', $normalized)
                    ->first();

                if ($record && $record->product) {
                    return [
                        'product' => $record->product,
                        'variant' => $record->variant,
                        'match_type' => "identifier:{$type}",
                        'confidence' => 1.0,
                    ];
                }
            }
        }

        // 2. Direct Canonical Model + Brand lookup
        if (!empty($payload['brand_name']) && !empty($payload['model_number'])) {
            $brand = Brand::where('name', $payload['brand_name'])
                ->orWhere('slug', Str::slug($payload['brand_name']))
                ->first();

            if ($brand) {
                $product = Product::where('brand_id', $brand->id)
                    ->where('model_number', trim($payload['model_number']))
                    ->first();

                if ($product) {
                    return [
                        'product' => $product,
                        'variant' => null,
                        'match_type' => 'brand_model',
                        'confidence' => 0.95,
                    ];
                }
            }
        }

        return [
            'product' => null,
            'variant' => null,
            'match_type' => null,
            'confidence' => 0.0,
        ];
    }

    /**
     * Attach a new identifier to an existing canonical product.
     */
    public function registerIdentifier(
        Product $product,
        string $type,
        string $value,
        ?ProductVariant $variant = null
    ): ?ProductIdentifier {
        $normalized = ProductIdentifier::normalize($type, $value);
        if (empty($normalized)) {
            return null;
        }

        return ProductIdentifier::firstOrCreate(
            [
                'type' => $type,
                'normalized_value' => $normalized,
            ],
            [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'value' => trim($value),
            ]
        );
    }
}

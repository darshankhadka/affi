<?php

namespace App\DTOs;

class NormalizedOfferDTO
{
    public function __construct(
        public readonly string $retailerDomain, // e.g. "amazon.com"
        public readonly string $retailerName,   // e.g. "Amazon"
        public readonly string $sku,            // Retailer specific ID / ASIN
        public readonly string $title,
        public readonly float $price,
        public readonly ?float $originalPrice,
        public readonly string $currencyCode,   // e.g. "USD", "GBP", "EUR"
        public readonly string $availability,   // 'in_stock', 'out_of_stock', 'preorder', 'discontinued'
        public readonly string $condition,      // 'new', 'refurbished', 'used'
        public readonly string $affiliateUrl,
        public readonly ?string $originalUrl = null,
        public readonly ?float $shippingCost = null,
        public readonly ?string $marketCode = 'us'
    ) {
    }
}

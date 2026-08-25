<?php

namespace App\DTOs;

class NormalizedOfferDTO
{
    public function __construct(
        public readonly string $retailerDomain,  // e.g. "amazon.com"
        public readonly string $retailerName,    // e.g. "Amazon"
        public readonly string $sku,             // Retailer-specific ID / ASIN
        public readonly string $title,
        public readonly float  $price,
        public readonly ?float $originalPrice,
        public readonly string $currencyCode,    // e.g. "USD", "GBP", "EUR"
        public readonly string $availability,    // 'in_stock', 'out_of_stock', 'preorder', 'backorder', 'discontinued'
        public readonly string $condition,       // 'new', 'refurbished', 'used'
        public readonly string $affiliateUrl,
        public readonly ?string $originalUrl    = null,
        public readonly ?float  $shippingCost   = null,
        public readonly ?string $marketCode     = 'us',
        public readonly ?string $merchantId     = null,  // Provider-specific advertiser/merchant ID
        public readonly ?string $providerCode   = null,  // 'amazon', 'awin', 'cj', etc.
        public readonly ?string $programmeId    = null,  // External programme ID for approval enforcement
    ) {
    }
}

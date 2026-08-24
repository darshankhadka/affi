<?php

namespace App\DTOs;

/**
 * Immutable input for deterministic retailer identity resolution.
 */
final class RetailerIdentityInput
{
    public function __construct(
        public readonly ?int $providerId,
        public readonly ?string $advertiserId,   // Awin programme/advertiser id (stable external identity)
        public readonly ?string $providerCode,    // e.g. 'awin'
        public readonly string $name,             // merchant/feed name (not the canonical key)
        public readonly ?string $domain,          // raw merchant domain from feed
        public readonly ?string $marketCode = null,
        public readonly ?string $currencyCode = null,
        public readonly ?string $websiteUrl = null,
    ) {
    }
}

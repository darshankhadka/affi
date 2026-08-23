<?php

namespace App\DTOs;

class NormalizedProductDTO
{
    /**
     * @param NormalizedIdentifierDTO[] $identifiers
     * @param NormalizedSpecificationDTO[] $specifications
     * @param NormalizedImageDTO[] $images
     */
    public function __construct(
        public readonly string $name,
        public readonly string $brandName,
        public readonly ?string $categorySlug,
        public readonly ?string $modelNumber,
        public readonly ?string $description,
        public readonly ?string $shortDescription,
        public readonly ?string $canonicalUpc,
        public readonly ?string $canonicalEan,
        public readonly ?string $canonicalMpn,
        public readonly array $identifiers,
        public readonly array $specifications,
        public readonly array $images,
        public readonly ?NormalizedOfferDTO $offer = null,
        public readonly ?string $providerCode = 'amazon',
        public readonly ?string $externalId = null
    ) {
    }
}

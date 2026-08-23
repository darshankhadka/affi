<?php

namespace App\DTOs;

class RawProductDTO
{
    public function __construct(
        public readonly string $providerCode,
        public readonly string $externalId,
        public readonly array $rawPayload,
        public readonly string $marketCode
    ) {
    }
}

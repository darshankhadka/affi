<?php

namespace App\DTOs;

class NormalizedImageDTO
{
    public function __construct(
        public readonly string $url,
        public readonly ?string $altText = null,
        public readonly bool $isPrimary = false,
        public readonly int $displayOrder = 0,
        public readonly ?int $width = null,
        public readonly ?int $height = null
    ) {
    }
}

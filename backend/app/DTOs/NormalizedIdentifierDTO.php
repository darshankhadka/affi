<?php

namespace App\DTOs;

class NormalizedIdentifierDTO
{
    public function __construct(
        public readonly string $type, // UPC, EAN, GTIN, ASIN, MPN, SKU
        public readonly string $value,
        public readonly string $normalizedValue
    ) {
    }

    public static function from(string $type, string $value): self
    {
        $cleaned = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $value)));
        return new self($type, trim($value), $cleaned);
    }
}

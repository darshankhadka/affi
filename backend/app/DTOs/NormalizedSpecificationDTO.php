<?php

namespace App\DTOs;

class NormalizedSpecificationDTO
{
    public function __construct(
        public readonly string $groupName,
        public readonly string $specName,
        public readonly string $specValue,
        public readonly int $displayOrder = 0
    ) {
    }
}

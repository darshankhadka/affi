<?php

namespace App\Services\Normalization;

use App\DTOs\NormalizedIdentifierDTO;
use App\DTOs\NormalizedProductDTO;

class ProductNormalizer
{
    /**
     * Known brand alias map for canonical consistency
     */
    protected array $brandAliases = [
        'apple inc' => 'Apple',
        'apple computer' => 'Apple',
        'apple computer inc' => 'Apple',
        'apple inc.' => 'Apple',
        'nvidia corp' => 'NVIDIA',
        'nvidia corporation' => 'NVIDIA',
        'asustek' => 'Asus',
        'asustek computer' => 'Asus',
        'asustek computer inc' => 'Asus',
        'asustek computer inc.' => 'Asus',
        'asus rog' => 'Asus',
        'intel corp' => 'Intel',
        'intel corporation' => 'Intel',
        'advanced micro devices' => 'AMD',
        'amd corp' => 'AMD',
        'samsung electronics' => 'Samsung',
        'sony interactive entertainment' => 'Sony',
        'lenovo group' => 'Lenovo',
        'dell inc' => 'Dell',
        'dell inc.' => 'Dell',
        'hp inc' => 'HP',
        'hp inc.' => 'HP',
        'hewlett packard' => 'HP',
        'corsair gaming' => 'Corsair',
        'logitech g' => 'Logitech',
    ];

    /**
     * Normalize a product payload into a clean, canonical DTO
     */
    public function normalize(NormalizedProductDTO $dto): NormalizedProductDTO
    {
        $normalizedBrand = $this->normalizeBrand($dto->brandName);
        $normalizedModel = $this->normalizeModel($dto->modelNumber);
        $normalizedName = $this->normalizeTitle($dto->name, $normalizedBrand);
        
        $normalizedIdentifiers = [];
        foreach ($dto->identifiers as $id) {
            $cleaned = $this->normalizeIdentifier($id->type, $id->value);
            if ($cleaned) {
                $normalizedIdentifiers[] = $cleaned;
            }
        }

        return new NormalizedProductDTO(
            name: $normalizedName,
            brandName: $normalizedBrand,
            categorySlug: $dto->categorySlug,
            modelNumber: $normalizedModel,
            description: trim($dto->description ?? ''),
            shortDescription: $dto->shortDescription ? trim($dto->shortDescription) : substr($normalizedName, 0, 250),
            canonicalUpc: $this->cleanDigits($dto->canonicalUpc, 12),
            canonicalEan: $this->cleanDigits($dto->canonicalEan, 13),
            canonicalMpn: $dto->canonicalMpn ? strtoupper(trim($dto->canonicalMpn)) : null,
            identifiers: $normalizedIdentifiers,
            specifications: $dto->specifications,
            images: $dto->images,
            offer: $dto->offer,
            providerCode: $dto->providerCode,
            externalId: $dto->externalId
        );
    }

    /**
     * Normalize brand name
     */
    public function normalizeBrand(string $raw): string
    {
        $trimmed = trim($raw);
        $cleaned = strtolower(trim(preg_replace('/[.,]/', '', $trimmed)));

        if (isset($this->brandAliases[$cleaned])) {
            return $this->brandAliases[$cleaned];
        }

        // Check against raw lowercase as well
        $lower = strtolower($trimmed);
        if (isset($this->brandAliases[$lower])) {
            return $this->brandAliases[$lower];
        }

        return ucwords($trimmed);
    }

    /**
     * Normalize model number
     */
    public function normalizeModel(?string $raw): ?string
    {
        if (empty($raw)) {
            return null;
        }

        $cleaned = trim($raw);
        // Remove trailing commas, brackets, or "Model: " prefixes
        $cleaned = preg_replace('/^model\s*:\s*/i', '', $cleaned);
        $cleaned = trim($cleaned, " ,.-;:\t\n\r\0\x0B");

        return !empty($cleaned) ? $cleaned : null;
    }

    /**
     * Clean noisy retailer marketing text from product title without destroying real model specs
     */
    public function normalizeTitle(string $raw, string $brand): string
    {
        $cleaned = trim($raw);

        // Remove typical promotional noise phrases at the end of titles
        $noisePatterns = [
            '/\s*[-|]\s*free shipping\s*/i',
            '/\s*[-|]\s*limited time deal\s*/i',
            '/\s*\[newest version\]\s*/i',
            '/\s*\[latest model\]\s*/i',
            '/\s*\(renewed\)\s*/i',
            '/\s*[-|]\s*fast delivery\s*/i',
        ];

        $cleaned = preg_replace($noisePatterns, '', $cleaned);
        $cleaned = trim($cleaned, " ,.-;|:\t\n\r\0\x0B");

        return $cleaned;
    }

    /**
     * Normalize identifier
     */
    public function normalizeIdentifier(string $type, string $value): ?NormalizedIdentifierDTO
    {
        $typeUpper = strtoupper(trim($type));
        $valTrimmed = trim($value);

        if (empty($valTrimmed)) {
            return null;
        }

        $normalizedVal = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $valTrimmed));

        // Format specific validations
        switch ($typeUpper) {
            case 'UPC':
                // UPC is usually 12 digits
                if (strlen($normalizedVal) === 11) {
                    $normalizedVal = '0' . $normalizedVal;
                }
                break;
            case 'EAN':
                // EAN is usually 13 digits
                if (strlen($normalizedVal) === 12) {
                    $normalizedVal = '0' . $normalizedVal;
                }
                break;
            case 'ASIN':
                // ASIN is exactly 10 alphanumeric characters
                if (strlen($normalizedVal) !== 10) {
                    return null;
                }
                break;
        }

        return new NormalizedIdentifierDTO($typeUpper, $valTrimmed, $normalizedVal);
    }

    protected function cleanDigits(?string $val, int $expectedLen): ?string
    {
        if (empty($val)) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $val);
        if (strlen($digits) === ($expectedLen - 1)) {
            $digits = '0' . $digits;
        }
        return strlen($digits) === $expectedLen ? $digits : null;
    }
}

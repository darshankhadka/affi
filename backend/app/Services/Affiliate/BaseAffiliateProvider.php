<?php

namespace App\Services\Affiliate;

use App\DTOs\NormalizedProductDTO;
use App\Models\AffiliateProvider;
use App\Models\Currency;
use App\Models\Market;
use App\Models\Offer;

abstract class BaseAffiliateProvider implements AffiliateProviderInterface
{
    public function supportsMarket(Market|string $market): bool
    {
        $code = $market instanceof Market ? strtolower($market->code) : strtolower($market);
        return in_array($code, array_map('strtolower', $this->getSupportedMarkets()), true);
    }

    public function supportsCurrency(Currency|string $currency): bool
    {
        $code = $currency instanceof Currency ? strtoupper($currency->code) : strtoupper($currency);
        return in_array($code, array_map('strtoupper', $this->getSupportedCurrencies()), true);
    }

    public function supportsProductFeed(): bool
    {
        return false;
    }

    public function supportsApi(): bool
    {
        return true;
    }

    public function supportsDeepLinks(): bool
    {
        return true;
    }

    public function getProduct(string $identifierType, string $identifierValue, Market $market): ?NormalizedProductDTO
    {
        $offers = $this->fetchProductOffers($identifierType, $identifierValue, $market);
        if (empty($offers)) {
            return null;
        }

        $first = $offers[0];
        return new NormalizedProductDTO(
            name: $first['title'],
            brand: 'Generic',
            category: 'Laptops',
            modelNumber: null,
            shortDescription: $first['title'],
            primaryImageUrl: null,
            identifiers: [$identifierType => $identifierValue],
            offers: $offers
        );
    }

    public function getProducts(array $identifiers, Market $market): array
    {
        $products = [];
        foreach ($identifiers as $type => $value) {
            $prod = $this->getProduct($type, $value, $market);
            if ($prod) {
                $products[] = $prod;
            }
        }
        return $products;
    }

    public function getOffers(string $identifierType, string $identifierValue, Market $market): array
    {
        return $this->fetchProductOffers($identifierType, $identifierValue, $market);
    }

    public function syncCatalogBatch(Market $market, int $limit = 50, ?string $cursor = null): array
    {
        return [
            'processed' => 0,
            'items' => [],
            'next_cursor' => null,
            'has_more' => false,
        ];
    }

    public function getRateLimit(): int
    {
        return 60;
    }

    public function getSupportedCategories(): array
    {
        return [
            'laptops', 'gaming-laptops', 'macbooks', 'desktops-mini-pcs',
            'smartphones', 'tablets-ipads', 'smartwatches', 'gpus-graphics-cards',
            'cpus-processors', 'ram-memory', 'ssds-storage', 'motherboards',
            'power-supplies-cases', 'gaming-monitors', '4k-oled-tvs',
            'mechanical-keyboards', 'gaming-mice', 'headphones-audio',
            'routers-mesh-wifi', 'cables-docks',
        ];
    }
}

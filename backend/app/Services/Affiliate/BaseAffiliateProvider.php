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

    /**
     * Default single-product lookup: delegates to fetchProductOffers() and assembles
     * a minimal NormalizedProductDTO from the first offer returned.
     *
     * Subclasses should override this with a proper single-item API call where possible.
     */
    public function getProduct(string $identifierType, string $identifierValue, Market $market): ?NormalizedProductDTO
    {
        $products = $this->searchProducts($identifierValue, $market, null, 1);
        return !empty($products) ? $products[0] : null;
    }

    public function getProducts(array $identifiers, Market $market): array
    {
        $products = [];
        foreach ($identifiers as $type => $value) {
            $prod = $this->getProduct((string) $type, (string) $value, $market);
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
            'processed'   => 0,
            'items'       => [],
            'next_cursor' => null,
            'has_more'    => false,
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

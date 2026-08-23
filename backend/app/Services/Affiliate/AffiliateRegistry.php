<?php

namespace App\Services\Affiliate;

use InvalidArgumentException;

class AffiliateRegistry
{
    /**
     * @var array<string, AffiliateProviderInterface>
     */
    protected array $providers = [];

    public function __construct()
    {
        $this->register(new AmazonProvider());
    }

    public function register(AffiliateProviderInterface $provider): void
    {
        $this->providers[$provider->getCode()] = $provider;
    }

    public function get(string $code): AffiliateProviderInterface
    {
        if (!isset($this->providers[$code])) {
            throw new InvalidArgumentException("Affiliate provider [{$code}] is not registered.");
        }

        return $this->providers[$code];
    }

    public function has(string $code): bool
    {
        return isset($this->providers[$code]);
    }

    /**
     * @return array<string, AffiliateProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }
}

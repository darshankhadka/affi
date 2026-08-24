<?php

namespace App\Services\Affiliate;

use InvalidArgumentException;

class AffiliateRegistry
{
    /**
     * @var array<string, AffiliateProviderInterface>
     */
    protected array $providers = [];

    public function __construct(
        ?AmazonProvider $amazon = null,
        ?AwinProvider $awin = null,
        ?CjProvider $cj = null,
        ?ImpactProvider $impact = null,
        ?TradeDoublerProvider $tradedoubler = null,
        ?RakutenProvider $rakuten = null,
        ?PartnerizeProvider $partnerize = null,
        ?DirectFeedProvider $direct = null
    ) {
        $this->register($amazon ?? new AmazonProvider());
        $this->register($awin ?? new AwinProvider());
        $this->register($cj ?? new CjProvider());
        $this->register($impact ?? new ImpactProvider());
        $this->register($tradedoubler ?? new TradeDoublerProvider());
        $this->register($rakuten ?? new RakutenProvider());
        $this->register($partnerize ?? new PartnerizeProvider());
        $this->register($direct ?? new DirectFeedProvider());
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

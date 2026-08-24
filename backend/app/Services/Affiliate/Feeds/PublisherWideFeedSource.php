<?php

namespace App\Services\Affiliate\Feeds;

use App\Models\Market;

/**
 * Publisher-wide Awin Product Datafeed (Architecture A).
 *
 * A single configured URL (AWIN_DATAFEED_URL) that contains the merchant's
 * full joined-programme catalogue. Downloaded exactly once per run and shared
 * across all advertisers.
 */
class PublisherWideFeedSource implements FeedSource
{
    protected string $url;
    /** @var int[] */
    protected array $advertiserIds;
    /** @var array<string, mixed> */
    protected array $config;
    protected ?string $marketFilter;

    /**
     * @param int[] $advertiserIds
     * @param array<string, mixed> $config
     */
    public function __construct(
        string $url,
        array $advertiserIds = [],
        array $config = [],
        ?string $marketFilter = null
    ) {
        $this->url = $url;
        $this->advertiserIds = array_map('intval', $advertiserIds);
        $this->config = $config;
        $this->marketFilter = $marketFilter;
    }

    public function getType(): string
    {
        return 'publisher_wide';
    }

    public function getName(): string
    {
        return 'Awin Publisher-Wide Product Datafeed';
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getAdvertiserIds(): array
    {
        return $this->advertiserIds;
    }

    public function supportsMarket(Market $market): bool
    {
        if ($this->marketFilter === null) {
            return true;
        }
        return strtolower($market->code) === strtolower($this->marketFilter);
    }

    public function getConfig(): array
    {
        return array_merge($this->config, [
            'feed_type' => 'publisher_wide',
            'has_configured_url' => !empty($this->url),
            'advertiser_count' => count($this->advertiserIds),
        ]);
    }

    public function isShared(): bool
    {
        return true;
    }
}

<?php

namespace App\Services\Affiliate\Feeds;

use App\Models\Market;

/**
 * Advertiser-specific Awin Product Datafeed (Architecture B).
 *
 * Built from AWIN_DATAFEED_API_KEY + /mid/{advertiserId}. One feed per joined
 * programme. Used only when no publisher-wide feed URL is configured.
 */
class AdvertiserSpecificFeedSource implements FeedSource
{
    protected string $baseUrl;
    protected int $advertiserId;
    /** @var array<string, mixed> */
    protected array $config;
    protected string $language;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        string $baseUrl,
        int $advertiserId,
        array $config = [],
        string $language = 'en'
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->advertiserId = $advertiserId;
        $this->config = $config;
        $this->language = $language;
    }

    public function getType(): string
    {
        return 'advertiser_specific';
    }

    public function getName(): string
    {
        return "Awin Advertiser Feed (MID: {$this->advertiserId})";
    }

    public function getUrl(): string
    {
        $columns = $this->config['columns'] ?? '';
        $compression = $this->config['compression'] ?? 'gzip';
        $delimiter = $this->config['delimiter'] ?? '%2C';
        $format = $this->config['format'] ?? 'csv';

        return "{$this->baseUrl}/"
            . "apikey/{$this->config['api_key']}/"
            . "language/{$this->language}/"
            . "mid/{$this->advertiserId}/"
            . "columns/{$columns}/"
            . "format/{$format}/"
            . "delimiter/{$delimiter}/"
            . "compression/{$compression}/";
    }

    public function getAdvertiserIds(): array
    {
        return [$this->advertiserId];
    }

    public function supportsMarket(Market $market): bool
    {
        return true; // Advertiser feed is inherently market-scoped via language/currency.
    }

    public function getConfig(): array
    {
        return array_merge($this->config, [
            'feed_type' => 'advertiser_specific',
            'advertiser_id' => $this->advertiserId,
            'language' => $this->language,
        ]);
    }

    public function isShared(): bool
    {
        return false;
    }
}

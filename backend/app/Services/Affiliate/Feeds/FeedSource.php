<?php

namespace App\Services\Affiliate\Feeds;

use App\Models\Market;

interface FeedSource
{
    /**
     * Unique type identifier for this feed source.
     * Examples: 'publisher_wide', 'advertiser_specific', 'api'
     */
    public function getType(): string;

    /**
     * Human-readable name for diagnostics/logging.
     */
    public function getName(): string;

    /**
     * Get the download URL for this feed source.
     */
    public function getUrl(): string;

    /**
     * Get advertiser/programme IDs this feed source covers.
     * Empty array = all joined advertisers (publisher-wide).
     *
     * @return int[]
     */
    public function getAdvertiserIds(): array;

    /**
     * Check if this feed source supports the given market.
     */
    public function supportsMarket(Market $market): bool;

    /**
     * Get safe configuration metadata for this feed source (NO secrets).
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array;

    /**
     * Whether this feed source should be downloaded once per run
     * and shared across multiple advertiser processing.
     */
    public function isShared(): bool;
}

<?php

namespace App\Services\Affiliate\Feeds;

use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Services\Affiliate\AwinDatafeedService;

/**
 * Resolves the correct Awin FeedSource from configuration with deterministic precedence:
 *
 *   1. Explicit AWIN_DATAFEED_URL (publisher-wide, Architecture A) — preferred.
 *   2. AWIN_DATAFEED_API_KEY + advertiser id (advertiser-specific, Architecture B).
 *   3. Nothing configured -> null.
 *
 * A publisher-wide feed is never reconstructed from an API key, and an advertiser
 * feed is never assumed when a publisher-wide URL is present.
 */
class FeedSourceFactory
{
    public const TYPE_PUBLISHER_WIDE = 'publisher_wide';
    public const TYPE_ADVERTISER_SPECIFIC = 'advertiser_specific';

    public static function createForAwin(
        array $providerConfig,
        array $appConfig,
        ?int $advertiserId = null,
        ?Market $market = null
    ): ?FeedSource {
        // 1. Publisher-wide configured feed URL takes precedence.
        $configuredFeedUrl = $providerConfig['datafeed_url'] ?? $appConfig['datafeed_url'] ?? null;

        if (!empty($configuredFeedUrl)) {
            $advertiserIds = [];
            if (!empty($providerConfig['advertiser_ids']) && is_array($providerConfig['advertiser_ids'])) {
                $advertiserIds = array_map('intval', $providerConfig['advertiser_ids']);
            }

            $marketFilter = null;
            if ($market) {
                $marketFilter = strtolower($market->code);
            }

            return new PublisherWideFeedSource(
                $configuredFeedUrl,
                $advertiserIds,
                [
                    'api_key' => $providerConfig['datafeed_api_key'] ?? $appConfig['datafeed_api_key'] ?? null,
                ],
                $marketFilter
            );
        }

        // 2. Advertiser-specific feed from API key + advertiser id (Architecture B).
        $apiKey = $providerConfig['datafeed_api_key'] ?? $appConfig['datafeed_api_key'] ?? null;

        if (!empty($apiKey) && $advertiserId && $advertiserId > 0) {
            return new AdvertiserSpecificFeedSource(
                'https://productdata.awin.com/datafeed/download',
                $advertiserId,
                [
                    'api_key' => $apiKey,
                    'columns' => implode(',', AwinDatafeedService::COMPREHENSIVE_FEED_COLUMNS),
                    'compression' => 'gzip',
                    'delimiter' => '%2C',
                    'format' => 'csv',
                ],
                self::resolveLanguage($market)
            );
        }

        return null;
    }

    /**
     * @param int[] $advertiserIds
     * @return FeedSource[]
     */
    public static function createAdvertiserFeedsForAwin(
        array $providerConfig,
        array $appConfig,
        array $advertiserIds,
        ?Market $market = null
    ): array {
        // Publisher-wide feed present -> no per-advertiser feeds needed.
        $configuredFeedUrl = $providerConfig['datafeed_url'] ?? $appConfig['datafeed_url'] ?? null;
        if (!empty($configuredFeedUrl)) {
            return [];
        }

        $feeds = [];
        foreach ($advertiserIds as $id) {
            $feed = self::createForAwin($providerConfig, $appConfig, (int) $id, $market);
            if ($feed) {
                $feeds[] = $feed;
            }
        }
        return $feeds;
    }

    /**
     * Map a market to a feed language. Market != language, so this is an explicit
     * mapping (not a blind country->language assumption). Falls back to market code.
     */
    public static function resolveLanguage(?Market $market): string
    {
        if (!$market) {
            return 'en';
        }

        $map = [
            'gb' => 'en', 'uk' => 'en', 'ie' => 'en', 'us' => 'en',
            'de' => 'de', 'at' => 'de', 'ch' => 'de',
            'fr' => 'fr', 'be' => 'fr',
            'es' => 'es',
            'it' => 'it',
            'nl' => 'nl',
            'pt' => 'pt',
            'fi' => 'fi',
            'se' => 'sv',
            'dk' => 'da',
            'pl' => 'pl',
            'cz' => 'cs',
        ];

        return $map[strtolower($market->code)] ?? strtolower($market->code);
    }
}

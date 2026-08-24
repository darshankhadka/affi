<?php

namespace Database\Seeders;

use App\Models\AffiliateProvider;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Market;
use App\Models\Retailer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GlobalRetailerMatrixSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Providers Exist
        $providers = [
            'amazon' => ['name' => 'Amazon Associates & PA-API 5.0', 'type' => 'api', 'is_active' => false, 'status' => 'disconnected'],
            'awin' => ['name' => 'Awin Publisher Network', 'type' => 'api', 'is_active' => true, 'status' => 'connected'],
            'cj' => ['name' => 'CJ Affiliate (Commission Junction)', 'type' => 'api', 'is_active' => true, 'status' => 'disconnected'],
            'impact' => ['name' => 'Impact (Impact.com)', 'type' => 'api', 'is_active' => true, 'status' => 'disconnected'],
            'tradedoubler' => ['name' => 'TradeDoubler', 'type' => 'api', 'is_active' => false, 'status' => 'not_configured'],
            'rakuten' => ['name' => 'Rakuten Advertising', 'type' => 'api', 'is_active' => false, 'status' => 'not_configured'],
            'partnerize' => ['name' => 'Partnerize', 'type' => 'api', 'is_active' => false, 'status' => 'not_configured'],
            'direct' => ['name' => 'Direct Retailer Datafeed', 'type' => 'datafeed', 'is_active' => false, 'status' => 'not_configured'],
        ];

        $providerModels = [];
        foreach ($providers as $code => $data) {
            $providerModels[$code] = AffiliateProvider::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'is_active' => $data['is_active'],
                    'status' => $data['status'],
                    'rate_limit_per_minute' => 60,
                ]
            );
        }

        // 2. Currencies (16 global currencies)
        $currenciesData = [
            'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'rate_to_usd' => 1.0, 'decimals' => 2],
            'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'CA$', 'rate_to_usd' => 0.74, 'decimals' => 2],
            'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'rate_to_usd' => 1.28, 'decimals' => 2],
            'EUR' => ['name' => 'Euro', 'symbol' => '€', 'rate_to_usd' => 1.09, 'decimals' => 2],
            'BGN' => ['name' => 'Bulgarian Lev', 'symbol' => 'лв', 'rate_to_usd' => 0.56, 'decimals' => 2],
            'CZK' => ['name' => 'Czech Koruna', 'symbol' => 'Kč', 'rate_to_usd' => 0.043, 'decimals' => 2],
            'DKK' => ['name' => 'Danish Krone', 'symbol' => 'kr.', 'rate_to_usd' => 0.15, 'decimals' => 2],
            'HUF' => ['name' => 'Hungarian Forint', 'symbol' => 'Ft', 'rate_to_usd' => 0.0028, 'decimals' => 0],
            'PLN' => ['name' => 'Polish Zloty', 'symbol' => 'zł', 'rate_to_usd' => 0.25, 'decimals' => 2],
            'RON' => ['name' => 'Romanian Leu', 'symbol' => 'lei', 'rate_to_usd' => 0.22, 'decimals' => 2],
            'SEK' => ['name' => 'Swedish Krona', 'symbol' => 'kr', 'rate_to_usd' => 0.096, 'decimals' => 2],
            'NOK' => ['name' => 'Norwegian Krone', 'symbol' => 'kr', 'rate_to_usd' => 0.094, 'decimals' => 2],
            'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF', 'rate_to_usd' => 1.14, 'decimals' => 2],
            'ISK' => ['name' => 'Icelandic Krona', 'symbol' => 'kr', 'rate_to_usd' => 0.0073, 'decimals' => 0],
            'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$', 'rate_to_usd' => 0.66, 'decimals' => 2],
            'NZD' => ['name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'rate_to_usd' => 0.61, 'decimals' => 2],
        ];

        $currencyModels = [];
        foreach ($currenciesData as $code => $cData) {
            $currencyModels[$code] = Currency::updateOrCreate(
                ['code' => $code],
                array_merge($cData, ['is_active' => true])
            );
        }

        // 3. 35 Locked Markets Matrix
        $marketsData = [
            // North America
            'us' => ['name' => 'United States', 'currency' => 'USD', 'locale' => 'en-US', 'hreflang' => 'en-us', 'country_name' => 'United States', 'iso2' => 'US', 'iso3' => 'USA'],
            'ca' => ['name' => 'Canada', 'currency' => 'CAD', 'locale' => 'en-CA', 'hreflang' => 'en-ca', 'country_name' => 'Canada', 'iso2' => 'CA', 'iso3' => 'CAN'],

            // United Kingdom
            'gb' => ['name' => 'United Kingdom', 'currency' => 'GBP', 'locale' => 'en-GB', 'hreflang' => 'en-gb', 'country_name' => 'United Kingdom', 'iso2' => 'GB', 'iso3' => 'GBR'],

            // European Union (24)
            'at' => ['name' => 'Austria', 'currency' => 'EUR', 'locale' => 'de-AT', 'hreflang' => 'de-at', 'country_name' => 'Austria', 'iso2' => 'AT', 'iso3' => 'AUT'],
            'be' => ['name' => 'Belgium', 'currency' => 'EUR', 'locale' => 'nl-BE', 'hreflang' => 'nl-be', 'country_name' => 'Belgium', 'iso2' => 'BE', 'iso3' => 'BEL'],
            'bg' => ['name' => 'Bulgaria', 'currency' => 'BGN', 'locale' => 'bg-BG', 'hreflang' => 'bg-bg', 'country_name' => 'Bulgaria', 'iso2' => 'BG', 'iso3' => 'BGR'],
            'hr' => ['name' => 'Croatia', 'currency' => 'EUR', 'locale' => 'hr-HR', 'hreflang' => 'hr-hr', 'country_name' => 'Croatia', 'iso2' => 'HR', 'iso3' => 'HRV'],
            'cy' => ['name' => 'Cyprus', 'currency' => 'EUR', 'locale' => 'el-CY', 'hreflang' => 'el-cy', 'country_name' => 'Cyprus', 'iso2' => 'CY', 'iso3' => 'CYP'],
            'cz' => ['name' => 'Czech Republic', 'currency' => 'CZK', 'locale' => 'cs-CZ', 'hreflang' => 'cs-cz', 'country_name' => 'Czech Republic', 'iso2' => 'CZ', 'iso3' => 'CZE'],
            'dk' => ['name' => 'Denmark', 'currency' => 'DKK', 'locale' => 'da-DK', 'hreflang' => 'da-dk', 'country_name' => 'Denmark', 'iso2' => 'DK', 'iso3' => 'DNK'],
            'ee' => ['name' => 'Estonia', 'currency' => 'EUR', 'locale' => 'et-EE', 'hreflang' => 'et-ee', 'country_name' => 'Estonia', 'iso2' => 'EE', 'iso3' => 'EST'],
            'fi' => ['name' => 'Finland', 'currency' => 'EUR', 'locale' => 'fi-FI', 'hreflang' => 'fi-fi', 'country_name' => 'Finland', 'iso2' => 'FI', 'iso3' => 'FIN'],
            'fr' => ['name' => 'France', 'currency' => 'EUR', 'locale' => 'fr-FR', 'hreflang' => 'fr-fr', 'country_name' => 'France', 'iso2' => 'FR', 'iso3' => 'FRA'],
            'de' => ['name' => 'Germany', 'currency' => 'EUR', 'locale' => 'de-DE', 'hreflang' => 'de-de', 'country_name' => 'Germany', 'iso2' => 'DE', 'iso3' => 'DEU'],
            'gr' => ['name' => 'Greece', 'currency' => 'EUR', 'locale' => 'el-GR', 'hreflang' => 'el-gr', 'country_name' => 'Greece', 'iso2' => 'GR', 'iso3' => 'GRC'],
            'hu' => ['name' => 'Hungary', 'currency' => 'HUF', 'locale' => 'hu-HU', 'hreflang' => 'hu-hu', 'country_name' => 'Hungary', 'iso2' => 'HU', 'iso3' => 'HUN'],
            'ie' => ['name' => 'Ireland', 'currency' => 'EUR', 'locale' => 'en-IE', 'hreflang' => 'en-ie', 'country_name' => 'Ireland', 'iso2' => 'IE', 'iso3' => 'IRL'],
            'it' => ['name' => 'Italy', 'currency' => 'EUR', 'locale' => 'it-IT', 'hreflang' => 'it-it', 'country_name' => 'Italy', 'iso2' => 'IT', 'iso3' => 'ITA'],
            'lv' => ['name' => 'Latvia', 'currency' => 'EUR', 'locale' => 'lv-LV', 'hreflang' => 'lv-lv', 'country_name' => 'Latvia', 'iso2' => 'LV', 'iso3' => 'LVA'],
            'lt' => ['name' => 'Lithuania', 'currency' => 'EUR', 'locale' => 'lt-LT', 'hreflang' => 'lt-lt', 'country_name' => 'Lithuania', 'iso2' => 'LT', 'iso3' => 'LTU'],
            'lu' => ['name' => 'Luxembourg', 'currency' => 'EUR', 'locale' => 'fr-LU', 'hreflang' => 'fr-lu', 'country_name' => 'Luxembourg', 'iso2' => 'LU', 'iso3' => 'LUX'],
            'mt' => ['name' => 'Malta', 'currency' => 'EUR', 'locale' => 'en-MT', 'hreflang' => 'en-mt', 'country_name' => 'Malta', 'iso2' => 'MT', 'iso3' => 'MLT'],
            'nl' => ['name' => 'Netherlands', 'currency' => 'EUR', 'locale' => 'nl-NL', 'hreflang' => 'nl-nl', 'country_name' => 'Netherlands', 'iso2' => 'NL', 'iso3' => 'NLD'],
            'pl' => ['name' => 'Poland', 'currency' => 'PLN', 'locale' => 'pl-PL', 'hreflang' => 'pl-pl', 'country_name' => 'Poland', 'iso2' => 'PL', 'iso3' => 'POL'],
            'pt' => ['name' => 'Portugal', 'currency' => 'EUR', 'locale' => 'pt-PT', 'hreflang' => 'pt-pt', 'country_name' => 'Portugal', 'iso2' => 'PT', 'iso3' => 'PRT'],
            'ro' => ['name' => 'Romania', 'currency' => 'RON', 'locale' => 'ro-RO', 'hreflang' => 'ro-ro', 'country_name' => 'Romania', 'iso2' => 'RO', 'iso3' => 'ROU'],
            'sk' => ['name' => 'Slovakia', 'currency' => 'EUR', 'locale' => 'sk-SK', 'hreflang' => 'sk-sk', 'country_name' => 'Slovakia', 'iso2' => 'SK', 'iso3' => 'SVK'],
            'si' => ['name' => 'Slovenia', 'currency' => 'EUR', 'locale' => 'sl-SI', 'hreflang' => 'sl-si', 'country_name' => 'Slovenia', 'iso2' => 'SI', 'iso3' => 'SVN'],
            'es' => ['name' => 'Spain', 'currency' => 'EUR', 'locale' => 'es-ES', 'hreflang' => 'es-es', 'country_name' => 'Spain', 'iso2' => 'ES', 'iso3' => 'ESP'],
            'se' => ['name' => 'Sweden', 'currency' => 'SEK', 'locale' => 'sv-SE', 'hreflang' => 'sv-se', 'country_name' => 'Sweden', 'iso2' => 'SE', 'iso3' => 'SWE'],

            // Europe Non-EU (3)
            'no' => ['name' => 'Norway', 'currency' => 'NOK', 'locale' => 'nb-NO', 'hreflang' => 'nb-no', 'country_name' => 'Norway', 'iso2' => 'NO', 'iso3' => 'NOR'],
            'ch' => ['name' => 'Switzerland', 'currency' => 'CHF', 'locale' => 'de-CH', 'hreflang' => 'de-ch', 'country_name' => 'Switzerland', 'iso2' => 'CH', 'iso3' => 'CHE'],
            'is' => ['name' => 'Iceland', 'currency' => 'ISK', 'locale' => 'is-IS', 'hreflang' => 'is-is', 'country_name' => 'Iceland', 'iso2' => 'IS', 'iso3' => 'ISL'],

            // Oceania (2)
            'au' => ['name' => 'Australia', 'currency' => 'AUD', 'locale' => 'en-AU', 'hreflang' => 'en-au', 'country_name' => 'Australia', 'iso2' => 'AU', 'iso3' => 'AUS'],
            'nz' => ['name' => 'New Zealand', 'currency' => 'NZD', 'locale' => 'en-NZ', 'hreflang' => 'en-nz', 'country_name' => 'New Zealand', 'iso2' => 'NZ', 'iso3' => 'NZL'],
        ];

        $order = 1;
        $marketModels = [];
        foreach ($marketsData as $code => $m) {
            $currencyModel = $currencyModels[$m['currency']] ?? $currencyModels['EUR'];
            $market = Market::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $m['name'],
                    'default_currency_id' => $currencyModel->id,
                    'locale' => $m['locale'],
                    'hreflang' => $m['hreflang'],
                    'is_active' => true,
                    'display_order' => $order++,
                ]
            );
            $marketModels[$code] = $market;

            Country::updateOrCreate(
                ['iso_code_2' => $m['iso2']],
                [
                    'iso_code_3' => $m['iso3'],
                    'name' => $m['country_name'],
                    'currency_id' => $currencyModel->id,
                    'market_id' => $market->id,
                ]
            );
        }

        // 4. 105 Locked Retailers Matrix (3 Priority Retailers per Market)
        $retailersMatrix = [
            // North America
            'us' => [
                ['name' => 'Amazon.com', 'slug' => 'amazon-us', 'domain' => 'amazon.com', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'Walmart', 'slug' => 'walmart-us', 'domain' => 'walmart.com', 'provider' => 'impact', 'network' => 'impact', 'type' => 'affiliate_network'],
                ['name' => 'Best Buy', 'slug' => 'best-buy-us', 'domain' => 'bestbuy.com', 'provider' => 'cj', 'network' => 'cj', 'type' => 'affiliate_network'],
            ],
            'ca' => [
                ['name' => 'Amazon.ca', 'slug' => 'amazon-ca', 'domain' => 'amazon.ca', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'Best Buy Canada', 'slug' => 'best-buy-ca', 'domain' => 'bestbuy.ca', 'provider' => 'cj', 'network' => 'cj', 'type' => 'affiliate_network'],
                ['name' => 'Walmart Canada', 'slug' => 'walmart-ca', 'domain' => 'walmart.ca', 'provider' => 'impact', 'network' => 'impact', 'type' => 'affiliate_network'],
            ],

            // United Kingdom
            'gb' => [
                ['name' => 'Amazon.co.uk', 'slug' => 'amazon-uk', 'domain' => 'amazon.co.uk', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'Currys', 'slug' => 'currys-uk', 'domain' => 'currys.co.uk', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Argos', 'slug' => 'argos-uk', 'domain' => 'argos.co.uk', 'provider' => 'cj', 'network' => 'cj', 'type' => 'affiliate_network'],
            ],

            // European Union
            'at' => [
                ['name' => 'Amazon.de', 'slug' => 'amazon-at', 'domain' => 'amazon.de', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'MediaMarkt Austria', 'slug' => 'mediamarkt-at', 'domain' => 'mediamarkt.at', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'e-tec', 'slug' => 'e-tec-at', 'domain' => 'e-tec.at', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'be' => [
                ['name' => 'Amazon.com.be', 'slug' => 'amazon-be', 'domain' => 'amazon.com.be', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'Coolblue', 'slug' => 'coolblue-be', 'domain' => 'coolblue.be', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'MediaMarkt', 'slug' => 'mediamarkt-be', 'domain' => 'mediamarkt.be', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'bg' => [
                ['name' => 'eMAG', 'slug' => 'emag-bg', 'domain' => 'emag.bg', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Technopolis', 'slug' => 'technopolis-bg', 'domain' => 'technopolis.bg', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Technomarket', 'slug' => 'technomarket-bg', 'domain' => 'technomarket.bg', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'hr' => [
                ['name' => 'eKupi', 'slug' => 'ekupi-hr', 'domain' => 'ekupi.hr', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Links', 'slug' => 'links-hr', 'domain' => 'links.hr', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Instar Informatika', 'slug' => 'instar-hr', 'domain' => 'instar-informatika.hr', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'cy' => [
                ['name' => 'Electroline', 'slug' => 'electroline-cy', 'domain' => 'electroline.com.cy', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Stephanis', 'slug' => 'stephanis-cy', 'domain' => 'stephanis.com.cy', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Public Cyprus', 'slug' => 'public-cy', 'domain' => 'public.cy', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'cz' => [
                ['name' => 'Alza', 'slug' => 'alza-cz', 'domain' => 'alza.cz', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Datart', 'slug' => 'datart-cz', 'domain' => 'datart.cz', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'CZC', 'slug' => 'czc-cz', 'domain' => 'czc.cz', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'dk' => [
                ['name' => 'Proshop', 'slug' => 'proshop-dk', 'domain' => 'proshop.dk', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Elgiganten', 'slug' => 'elgiganten-dk', 'domain' => 'elgiganten.dk', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'POWER', 'slug' => 'power-dk', 'domain' => 'power.dk', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'ee' => [
                ['name' => '1a.ee', 'slug' => '1a-ee', 'domain' => '1a.ee', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Euronics', 'slug' => 'euronics-ee', 'domain' => 'euronics.ee', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Arvutitark', 'slug' => 'arvutitark-ee', 'domain' => 'arvutitark.ee', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'fi' => [
                ['name' => 'Verkkokauppa.com', 'slug' => 'verkkokauppa-fi', 'domain' => 'verkkokauppa.com', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Gigantti', 'slug' => 'gigantti-fi', 'domain' => 'gigantti.fi', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Power', 'slug' => 'power-fi', 'domain' => 'power.fi', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'fr' => [
                ['name' => 'Amazon.fr', 'slug' => 'amazon-fr', 'domain' => 'amazon.fr', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'Fnac', 'slug' => 'fnac-fr', 'domain' => 'fnac.com', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Cdiscount', 'slug' => 'cdiscount-fr', 'domain' => 'cdiscount.com', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'de' => [
                ['name' => 'Amazon.de', 'slug' => 'amazon-de', 'domain' => 'amazon.de', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'MediaMarkt', 'slug' => 'mediamarkt-de', 'domain' => 'mediamarkt.de', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Cyberport', 'slug' => 'cyberport-de', 'domain' => 'cyberport.de', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'gr' => [
                ['name' => 'Skroutz', 'slug' => 'skroutz-gr', 'domain' => 'skroutz.gr', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Public', 'slug' => 'public-gr', 'domain' => 'public.gr', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Plaisio', 'slug' => 'plaisio-gr', 'domain' => 'plaisio.gr', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'hu' => [
                ['name' => 'eMAG', 'slug' => 'emag-hu', 'domain' => 'emag.hu', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Alza', 'slug' => 'alza-hu', 'domain' => 'alza.hu', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'MediaMarkt', 'slug' => 'mediamarkt-hu', 'domain' => 'mediamarkt.hu', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'ie' => [
                ['name' => 'Amazon.co.uk (IE)', 'slug' => 'amazon-ie', 'domain' => 'amazon.co.uk', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'Currys', 'slug' => 'currys-ie', 'domain' => 'currys.ie', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Harvey Norman', 'slug' => 'harvey-norman-ie', 'domain' => 'harveynorman.ie', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'it' => [
                ['name' => 'Amazon.it', 'slug' => 'amazon-it', 'domain' => 'amazon.it', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'MediaWorld', 'slug' => 'mediaworld-it', 'domain' => 'mediaworld.it', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Unieuro', 'slug' => 'unieuro-it', 'domain' => 'unieuro.it', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'lv' => [
                ['name' => '1a.lv', 'slug' => '1a-lv', 'domain' => '1a.lv', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'RD Electronics', 'slug' => 'rd-electronics-lv', 'domain' => 'rdveikals.lv', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Euronics', 'slug' => 'euronics-lv', 'domain' => 'euronics.lv', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'lt' => [
                ['name' => 'Varle', 'slug' => 'varle-lt', 'domain' => 'varle.lt', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => '1a.lt', 'slug' => '1a-lt', 'domain' => '1a.lt', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Topocentras', 'slug' => 'topocentras-lt', 'domain' => 'topocentras.lt', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'lu' => [
                ['name' => 'Amazon.de (LU)', 'slug' => 'amazon-lu-de', 'domain' => 'amazon.de', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'Amazon.fr (LU)', 'slug' => 'amazon-lu-fr', 'domain' => 'amazon.fr', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'MediaMarkt', 'slug' => 'mediamarkt-lu', 'domain' => 'mediamarkt.lu', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'mt' => [
                ['name' => 'Scan Malta', 'slug' => 'scan-malta', 'domain' => 'scanmalta.com', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Forestals', 'slug' => 'forestals-mt', 'domain' => 'forestals.com', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Klikk', 'slug' => 'klikk-mt', 'domain' => 'klikk.com.mt', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'nl' => [
                ['name' => 'Amazon.nl', 'slug' => 'amazon-nl', 'domain' => 'amazon.nl', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'Coolblue', 'slug' => 'coolblue-nl', 'domain' => 'coolblue.nl', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'bol', 'slug' => 'bol-nl', 'domain' => 'bol.com', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'pl' => [
                ['name' => 'Amazon.pl', 'slug' => 'amazon-pl', 'domain' => 'amazon.pl', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'Media Expert', 'slug' => 'media-expert-pl', 'domain' => 'mediaexpert.pl', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'x-kom', 'slug' => 'x-kom-pl', 'domain' => 'x-kom.pl', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'pt' => [
                ['name' => 'Amazon.es (PT)', 'slug' => 'amazon-pt', 'domain' => 'amazon.es', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'Worten', 'slug' => 'worten-pt', 'domain' => 'worten.pt', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'FNAC', 'slug' => 'fnac-pt', 'domain' => 'fnac.pt', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'ro' => [
                ['name' => 'eMAG', 'slug' => 'emag-ro', 'domain' => 'emag.ro', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Altex', 'slug' => 'altex-ro', 'domain' => 'altex.ro', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'PC Garage', 'slug' => 'pc-garage-ro', 'domain' => 'pcgarage.ro', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'sk' => [
                ['name' => 'Alza', 'slug' => 'alza-sk', 'domain' => 'alza.sk', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'NAY', 'slug' => 'nay-sk', 'domain' => 'nay.sk', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Datart', 'slug' => 'datart-sk', 'domain' => 'datart.sk', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'si' => [
                ['name' => 'Mimovrste', 'slug' => 'mimovrste-si', 'domain' => 'mimovrste.com', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Big Bang', 'slug' => 'big-bang-si', 'domain' => 'bigbang.si', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Harvey Norman', 'slug' => 'harvey-norman-si', 'domain' => 'harveynorman.si', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'es' => [
                ['name' => 'Amazon.es', 'slug' => 'amazon-es', 'domain' => 'amazon.es', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'MediaMarkt', 'slug' => 'mediamarkt-es', 'domain' => 'mediamarkt.es', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'PcComponentes', 'slug' => 'pccomponentes-es', 'domain' => 'pccomponentes.com', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'se' => [
                ['name' => 'Amazon.se', 'slug' => 'amazon-se', 'domain' => 'amazon.se', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'Elgiganten', 'slug' => 'elgiganten-se', 'domain' => 'elgiganten.se', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Webhallen', 'slug' => 'webhallen-se', 'domain' => 'webhallen.com', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],

            // Europe Non-EU
            'no' => [
                ['name' => 'Elkjøp', 'slug' => 'elkjop-no', 'domain' => 'elkjop.no', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Komplett', 'slug' => 'komplett-no', 'domain' => 'komplett.no', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Power', 'slug' => 'power-no', 'domain' => 'power.no', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'ch' => [
                ['name' => 'Digitec Galaxus', 'slug' => 'digitec-ch', 'domain' => 'digitec.ch', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Brack', 'slug' => 'brack-ch', 'domain' => 'brack.ch', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Interdiscount', 'slug' => 'interdiscount-ch', 'domain' => 'interdiscount.ch', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
            'is' => [
                ['name' => 'ELKO', 'slug' => 'elko-is', 'domain' => 'elko.is', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Origo', 'slug' => 'origo-is', 'domain' => 'origo.is', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Tölvutek', 'slug' => 'tolvutek-is', 'domain' => 'tolvutek.is', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],

            // Oceania
            'au' => [
                ['name' => 'Amazon.com.au', 'slug' => 'amazon-au', 'domain' => 'amazon.com.au', 'provider' => 'amazon', 'network' => 'amazon', 'type' => 'direct_api'],
                ['name' => 'JB Hi-Fi', 'slug' => 'jbhifi-au', 'domain' => 'jbhifi.com.au', 'provider' => 'impact', 'network' => 'impact', 'type' => 'affiliate_network'],
                ['name' => 'Officeworks', 'slug' => 'officeworks-au', 'domain' => 'officeworks.com.au', 'provider' => 'impact', 'network' => 'impact', 'type' => 'affiliate_network'],
            ],
            'nz' => [
                ['name' => 'PB Tech', 'slug' => 'pbtech-nz', 'domain' => 'pbtech.co.nz', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Noel Leeming', 'slug' => 'noelleeming-nz', 'domain' => 'noelleeming.co.nz', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
                ['name' => 'Mighty Ape', 'slug' => 'mightyape-nz', 'domain' => 'mightyape.co.nz', 'provider' => 'awin', 'network' => 'awin', 'type' => 'affiliate_network'],
            ],
        ];

        foreach ($retailersMatrix as $marketCode => $retailersList) {
            $m = $marketModels[$marketCode];
            $countryIso = strtoupper($marketsData[$marketCode]['iso2']);
            $currencyCode = $marketsData[$marketCode]['currency'];

            foreach ($retailersList as $r) {
                $providerModel = $providerModels[$r['provider']] ?? $providerModels['awin'];

                // Safe idempotent updateOrCreate without overwriting active live credentials
                Retailer::updateOrCreate(
                    ['slug' => $r['slug']],
                    [
                        'name' => $r['name'],
                        'code' => $r['slug'],
                        'domain' => $r['domain'],
                        'country' => $countryIso,
                        'market_code' => $marketCode,
                        'currency_code' => $currencyCode,
                        'affiliate_provider_id' => $providerModel->id,
                        'affiliate_network' => $r['network'],
                        'status' => 'not_configured',
                        'integration_type' => $r['type'],
                        'api_available' => in_array($r['type'], ['direct_api', 'api', 'hybrid']),
                        'feed_available' => in_array($r['type'], ['product_feed', 'csv_feed', 'xml_feed', 'datafeed', 'hybrid']),
                        'deep_link_supported' => true,
                        'price_tracking_supported' => true,
                        'website_url' => 'https://' . $r['domain'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Market;
use App\Models\Retailer;
use App\Services\Affiliate\AffiliateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalRetailerMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('system:init-foundation');
    }

    public function test_all_35_global_markets_and_16_currencies_exist(): void
    {
        $marketCodes = [
            'us', 'ca', 'gb', 'at', 'be', 'bg', 'hr', 'cy', 'cz', 'dk', 'ee', 'fi',
            'fr', 'de', 'gr', 'hu', 'ie', 'it', 'lv', 'lt', 'lu', 'mt', 'nl', 'pl',
            'pt', 'ro', 'sk', 'si', 'es', 'se', 'no', 'ch', 'is', 'au', 'nz',
        ];

        foreach ($marketCodes as $code) {
            $market = Market::where('code', $code)->first();
            $this->assertNotNull($market, "Market [{$code}] must exist in database.");
            $this->assertTrue($market->is_active, "Market [{$code}] must be active.");
            $this->assertNotNull($market->defaultCurrency, "Market [{$code}] must have a default currency.");
        }

        $currencyCodes = ['USD', 'CAD', 'GBP', 'EUR', 'BGN', 'CZK', 'DKK', 'HUF', 'PLN', 'RON', 'SEK', 'NOK', 'CHF', 'ISK', 'AUD', 'NZD'];
        foreach ($currencyCodes as $cur) {
            $this->assertDatabaseHas('currencies', ['code' => $cur, 'is_active' => true]);
        }
    }

    public function test_105_locked_retailers_are_seeded_with_valid_providers(): void
    {
        $retailersCount = Retailer::count();
        $this->assertGreaterThanOrEqual(105, $retailersCount);

        // Check key locked retailers
        $this->assertDatabaseHas('retailers', ['slug' => 'amazon-us', 'country' => 'US', 'currency_code' => 'USD']);
        $this->assertDatabaseHas('retailers', ['slug' => 'walmart-us', 'country' => 'US', 'currency_code' => 'USD']);
        $this->assertDatabaseHas('retailers', ['slug' => 'currys-uk', 'country' => 'GB', 'currency_code' => 'GBP']);
        $this->assertDatabaseHas('retailers', ['slug' => 'mediamarkt-de', 'country' => 'DE', 'currency_code' => 'EUR']);
        $this->assertDatabaseHas('retailers', ['slug' => 'fnac-fr', 'country' => 'FR', 'currency_code' => 'EUR']);
        $this->assertDatabaseHas('retailers', ['slug' => 'proshop-dk', 'country' => 'DK', 'currency_code' => 'DKK']);
        $this->assertDatabaseHas('retailers', ['slug' => 'digitec-ch', 'country' => 'CH', 'currency_code' => 'CHF']);
        $this->assertDatabaseHas('retailers', ['slug' => 'jbhifi-au', 'country' => 'AU', 'currency_code' => 'AUD']);
        $this->assertDatabaseHas('retailers', ['slug' => 'pbtech-nz', 'country' => 'NZ', 'currency_code' => 'NZD']);
    }

    public function test_provider_capability_detection(): void
    {
        $registry = app(AffiliateRegistry::class);

        $awin = $registry->get('awin');
        $this->assertTrue($awin->supportsProductFeed());
        $this->assertTrue($awin->supportsMarket('de'));
        $this->assertTrue($awin->supportsCurrency('EUR'));

        $amazon = $registry->get('amazon');
        $this->assertTrue($amazon->supportsApi());
        $this->assertTrue($amazon->supportsMarket('us'));
        $this->assertTrue($amazon->supportsCurrency('USD'));

        $tradedoubler = $registry->get('tradedoubler');
        $this->assertTrue($tradedoubler->supportsMarket('se'));
        $this->assertTrue($tradedoubler->supportsCurrency('SEK'));
    }
}

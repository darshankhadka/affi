<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('system:init-foundation');
    }

    public function test_detects_market_from_cf_ipcountry_header(): void
    {
        $response = $this->withHeaders(['CF-IPCountry' => 'DE'])->getJson('/api/v1/markets/detect');

        $response->assertStatus(200)
            ->assertJsonPath('data.market.code', 'de')
            ->assertJsonPath('data.detected_country', 'DE')
            ->assertJsonPath('data.fallback', false);
    }

    public function test_detects_denmark_from_x_country_code_header(): void
    {
        $response = $this->withHeaders(['X-Country-Code' => 'DK'])->getJson('/api/v1/markets/detect');

        $response->assertStatus(200)
            ->assertJsonPath('data.market.code', 'dk')
            ->assertJsonPath('data.detected_country', 'DK')
            ->assertJsonPath('data.fallback', false);
    }

    public function test_fallbacks_to_us_for_unsupported_asian_country(): void
    {
        $response = $this->withHeaders(['CF-IPCountry' => 'NP'])->getJson('/api/v1/markets/detect');

        $response->assertStatus(200)
            ->assertJsonPath('data.market.code', 'us')
            ->assertJsonPath('data.detected_country', 'NP')
            ->assertJsonPath('data.fallback', true);
    }

    public function test_detects_from_accept_language_header_when_no_country_headers(): void
    {
        $response = $this->withHeaders(['Accept-Language' => 'fr-FR,fr;q=0.9'])->getJson('/api/v1/markets/detect');

        $response->assertStatus(200)
            ->assertJsonPath('data.market.code', 'fr')
            ->assertJsonPath('data.detected_country', 'FR');
    }
}

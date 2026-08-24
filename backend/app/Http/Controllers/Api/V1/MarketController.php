<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\MarketResource;
use App\Models\Market;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends BaseApiController
{
    /**
     * List all available markets
     */
    public function index(Request $request): JsonResponse
    {
        $query = Market::with('defaultCurrency')->orderBy('display_order');
        
        // Non-admin requests only get active markets
        if (!$request->user()?->hasRole(['Super Admin', 'Admin'])) {
            $query->where('is_active', true);
        }

        $markets = $query->get();
        return $this->success(MarketResource::collection($markets));
    }

    /**
     * Get market details by code
     */
    public function show(string $code): JsonResponse
    {
        $market = Market::with('defaultCurrency')
            ->where('code', strtolower($code))
            ->first();

        if (!$market) {
            return $this->error('Market not found.', 404);
        }

        return $this->success(new MarketResource($market));
    }

    /**
     * Intelligent Geolocation Market Detection
     */
    public function detect(Request $request): JsonResponse
    {
        // 1. Check CDN & Reverse Proxy Geolocation Headers
        $country = strtoupper(trim(
            $request->input('country')
            ?: $request->header('CF-IPCountry')
            ?: $request->header('CloudFront-Viewer-Country')
            ?: $request->header('X-Country-Code')
            ?: $request->header('GEOIP_COUNTRY_CODE')
            ?: $request->header('X-Geo-Country')
            ?: ''
        ));

        // 2. If no header, check Accept-Language header
        if (empty($country)) {
            $acceptLang = (string) $request->header('Accept-Language');
            if (preg_match('/([a-z]{2})-([A-Z]{2})/i', $acceptLang, $matches)) {
                $country = strtoupper($matches[2]);
            }
        }

        // 3. Map Country Code to Canonical Market
        $marketCode = strtolower($country);
        $market = Market::with('defaultCurrency')
            ->where('code', $marketCode)
            ->where('is_active', true)
            ->first();

        // 4. Regional Fallback mappings
        if (!$market) {
            $euCountries = [
                'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR',
                'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK',
                'SI', 'ES', 'SE', 'NO', 'CH', 'IS',
            ];

            if (in_array($country, $euCountries, true)) {
                $market = Market::with('defaultCurrency')->where('code', 'de')->first();
            } elseif (in_array($country, ['GB', 'UK'], true)) {
                $market = Market::with('defaultCurrency')->where('code', 'gb')->first();
            } elseif ($country === 'CA') {
                $market = Market::with('defaultCurrency')->where('code', 'ca')->first();
            } elseif ($country === 'AU') {
                $market = Market::with('defaultCurrency')->where('code', 'au')->first();
            } elseif ($country === 'NZ') {
                $market = Market::with('defaultCurrency')->where('code', 'nz')->first();
            } else {
                // Global default fallback: United States (US)
                $market = Market::with('defaultCurrency')->where('code', 'us')->first()
                    ?? Market::with('defaultCurrency')->where('code', 'gb')->first();
            }
        }

        return $this->success([
            'detected_country' => $country ?: 'UNKNOWN',
            'market' => new MarketResource($market),
            'fallback' => empty($country) || strtolower($country) !== $market->code,
        ], 'Market detected successfully.');
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Currency;
use App\Models\Market;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketAdminController extends BaseApiController
{
    public function __construct(protected AuditLoggerService $auditLogger)
    {
    }

    public function markets(Request $request): JsonResponse
    {
        $markets = Market::with('defaultCurrency')->get();
        return $this->success($markets);
    }

    public function updateMarket(Request $request, int $id): JsonResponse
    {
        $market = Market::findOrFail($id);
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'default_currency_id' => ['sometimes', 'exists:currencies,id'],
        ]);

        $old = $market->toArray();
        $market->update($validated);
        $this->auditLogger->log('market.update', $market, $old, $market->toArray());

        return $this->success($market, 'Market updated successfully.');
    }

    public function currencies(Request $request): JsonResponse
    {
        $currencies = Currency::all();
        return $this->success($currencies);
    }
}

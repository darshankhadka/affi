<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\OfferResource;
use App\Models\Offer;
use App\Models\Product;
use App\Services\Audit\AuditLoggerService;
use App\Services\Pricing\BestPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfferAdminController extends BaseApiController
{
    public function __construct(
        protected BestPriceService $bestPriceService,
        protected AuditLoggerService $auditLogger
    ) {
    }

    /**
     * List all offers for admin
     */
    public function index(Request $request): JsonResponse
    {
        $query = Offer::with(['product.brand', 'retailer', 'market', 'currency']);

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($marketId = $request->input('market_id')) {
            $query->where('market_id', $marketId);
        }

        if ($retailerId = $request->input('retailer_id')) {
            $query->where('retailer_id', $retailerId);
        }

        if ($availability = $request->input('availability')) {
            $query->where('availability', $availability);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $offers = $query->latest()->paginate($perPage);

        return $this->paginated(OfferResource::collection($offers));
    }

    /**
     * Store new retailer offer manually
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'retailer_id' => ['required', 'exists:retailers,id'],
            'market_id' => ['required', 'exists:markets,id'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'sku' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'affiliate_url' => ['required', 'url'],
            'original_url' => ['nullable', 'url'],
            'price' => ['required', 'numeric', 'min:0'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'availability' => ['required', 'in:in_stock,out_of_stock,preorder,discontinued'],
            'condition' => ['required', 'in:new,refurbished,used'],
            'is_active' => ['boolean'],
        ]);

        $offer = Offer::create($validated);

        $this->bestPriceService->recordPriceHistory($offer);
        $this->bestPriceService->recalculate($offer->product, $offer->market);
        $this->auditLogger->log('offer.create', $offer, null, $offer->toArray());

        return $this->success(new OfferResource($offer->load(['retailer', 'currency', 'market'])), 'Offer created successfully.', 201);
    }

    /**
     * Update an offer
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $offer = Offer::findOrFail($id);

        $validated = $request->validate([
            'price' => ['sometimes', 'numeric', 'min:0'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'availability' => ['sometimes', 'in:in_stock,out_of_stock,preorder,discontinued'],
            'is_active' => ['sometimes', 'boolean'],
            'affiliate_url' => ['sometimes', 'url'],
        ]);

        $oldValues = $offer->toArray();
        $offer->update($validated);

        $this->bestPriceService->recordPriceHistory($offer);
        $this->bestPriceService->recalculate($offer->product, $offer->market);
        $this->auditLogger->log('offer.update', $offer, $oldValues, $offer->toArray());

        return $this->success(new OfferResource($offer->fresh(['retailer', 'currency', 'market'])), 'Offer updated successfully.');
    }

    /**
     * Delete an offer
     */
    public function destroy(int $id): JsonResponse
    {
        $offer = Offer::findOrFail($id);
        $product = $offer->product;
        $market = $offer->market;

        $this->auditLogger->log('offer.delete', $offer, $offer->toArray(), null);
        $offer->delete();

        $this->bestPriceService->recalculate($product, $market);

        return $this->success(null, 'Offer deleted successfully.');
    }

    /**
     * Trigger manual best price recalculation for a product
     */
    public function recalculate(int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $this->bestPriceService->recalculate($product);

        return $this->success(null, "Best prices recalculated for {$product->name}.");
    }
}

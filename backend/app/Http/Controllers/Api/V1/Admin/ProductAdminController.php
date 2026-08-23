<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Http\Resources\Api\V1\ProductListResource;
use App\Models\Product;
use App\Models\ProductIdentifier;
use App\Services\Audit\AuditLoggerService;
use App\Services\Matching\ProductMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductAdminController extends BaseApiController
{
    public function __construct(
        protected ProductMatchingService $matchingService,
        protected AuditLoggerService $auditLogger
    ) {
    }

    /**
     * List all products for admin management
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['brand', 'category', 'primaryImage'])
            ->withCount(['offers', 'variants']);

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('model_number', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $products = $query->latest()->paginate($perPage);

        return $this->paginated(ProductListResource::collection($products));
    }

    /**
     * Store a new canonical product
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand_id' => ['required', 'exists:brands,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'model_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:draft,published,archived'],
            'release_date' => ['nullable', 'date'],
            'canonical_upc' => ['nullable', 'string', 'max:50'],
            'canonical_ean' => ['nullable', 'string', 'max:50'],
            'canonical_mpn' => ['nullable', 'string', 'max:100'],
            'identifiers' => ['nullable', 'array'],
            'identifiers.*.type' => ['required', 'in:UPC,EAN,GTIN,MPN,ASIN,SKU'],
            'identifiers.*.value' => ['required', 'string'],
        ]);

        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-" . $counter++;
        }

        $product = Product::create([
            'brand_id' => $validated['brand_id'],
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'model_number' => $validated['model_number'] ?? null,
            'description' => $validated['description'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'status' => $validated['status'],
            'release_date' => $validated['release_date'] ?? null,
            'canonical_upc' => $validated['canonical_upc'] ?? null,
            'canonical_ean' => $validated['canonical_ean'] ?? null,
            'canonical_mpn' => $validated['canonical_mpn'] ?? null,
        ]);

        // Register identifiers
        if (!empty($validated['identifiers'])) {
            foreach ($validated['identifiers'] as $idData) {
                $this->matchingService->registerIdentifier($product, $idData['type'], $idData['value']);
            }
        }

        $this->auditLogger->log('product.create', $product, null, $product->toArray());

        return $this->success(new ProductDetailResource($product->load(['brand', 'category', 'identifiers'])), 'Product created successfully.', 201);
    }

    /**
     * Show detailed product for admin
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::with(['brand', 'category', 'images', 'specifications', 'variants', 'identifiers', 'offers.retailer', 'bestPrices.currency'])
            ->findOrFail($id);

        return $this->success(new ProductDetailResource($product));
    }

    /**
     * Update an existing product
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'brand_id' => ['sometimes', 'exists:brands,id'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'model_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'release_date' => ['nullable', 'date'],
        ]);

        $oldValues = $product->toArray();
        $product->update($validated);

        $this->auditLogger->log('product.update', $product, $oldValues, $product->toArray());

        return $this->success(new ProductDetailResource($product->fresh(['brand', 'category'])), 'Product updated successfully.');
    }

    /**
     * Delete product (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->auditLogger->log('product.delete', $product, $product->toArray(), null);
        $product->delete();

        return $this->success(null, 'Product deleted successfully.');
    }

    /**
     * Test matching pipeline on an arbitrary payload
     */
    public function match(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identifiers' => ['nullable', 'array'],
            'brand_name' => ['nullable', 'string'],
            'model_number' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
        ]);

        $result = $this->matchingService->match($validated);

        return $this->success([
            'matched' => $result['product'] !== null,
            'match_type' => $result['match_type'],
            'confidence' => $result['confidence'],
            'product' => $result['product'] ? new ProductListResource($result['product']->load(['brand', 'category'])) : null,
        ]);
    }
}

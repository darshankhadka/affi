<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Http\Resources\Api\V1\ProductListResource;
use App\Models\Category;
use App\Models\Market;
use App\Models\Product;
use App\Services\SEO\MetadataService;
use App\Services\SEO\StructuredDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends BaseApiController
{
    public function __construct(
        protected MetadataService $metadataService,
        protected StructuredDataService $structuredDataService
    ) {
    }

    /**
     * List products with market context, filtering, search, and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $marketCode = $request->input('market');
        $market = null;
        if (!empty($marketCode)) {
            $market = Market::where('code', strtolower(trim((string) $marketCode)))
                ->where('is_active', true)
                ->first();
        }

        $query = Product::where('status', 'published')
            ->whereHas('category', function ($q) {
                $q->where('is_active', true);
            });

        if ($market) {
            $query->whereHas('offers', function ($q) use ($market) {
                $q->where('market_id', $market->id)
                  ->where('is_active', true);
            });
        }

        $query->with([
            'brand',
            'category',
            'primaryImage',
            'bestPrices' => function ($q) use ($market) {
                if ($market) {
                    $q->where('market_id', $market->id);
                }
                $q->with('currency');
            },
        ]);

        // Category filter (supports category slug, ID, and descendant categories)
        if ($categoryParam = $request->input('category')) {
            $category = is_numeric($categoryParam)
                ? Category::find($categoryParam)
                : Category::where('slug', $categoryParam)->first();

            if ($category) {
                $categoryIds = Category::where('parent_id', $category->id)
                    ->pluck('id')
                    ->push($category->id)
                    ->toArray();

                $query->whereIn('category_id', $categoryIds);
            } else {
                $query->whereHas('category', function ($q) use ($categoryParam) {
                    $q->where('slug', $categoryParam);
                });
            }
        }

        // Brand filter
        if ($brandSlug = $request->input('brand')) {
            $query->whereHas('brand', function ($q) use ($brandSlug) {
                $q->where('slug', $brandSlug);
            });
        }

        // Search query across name, brand, category, model, and identifiers
        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('model_number', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%")
                  ->orWhere('canonical_ean', 'like', "%{$search}%")
                  ->orWhere('canonical_upc', 'like', "%{$search}%")
                  ->orWhere('canonical_mpn', 'like', "%{$search}%")
                  ->orWhereHas('brand', function ($b) use ($search) {
                      $b->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('category', function ($c) use ($search) {
                      $c->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sort = $request->input('sort', $request->filled('q') ? 'relevance' : 'newest');
        match ($sort) {
            'relevance' => $search ? $query->orderByRaw(
                "CASE 
                    WHEN name LIKE ? THEN 1 
                    WHEN name LIKE ? THEN 2 
                    ELSE 3 
                END",
                ["{$search}%", "%{$search}%"]
            )->latest() : $query->latest(),
            'oldest' => $query->oldest(),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->latest(),
        };

        $perPage = min(max((int) $request->input('per_page', 20), 1), 500);
        $products = $query->paginate($perPage);

        // Record search query intelligence
        if ($search) {
            \App\Models\SearchLog::create([
                'query' => substr($search, 0, 250),
                'market_id' => $market?->id,
                'results_count' => $products->total(),
                'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
                'created_at' => now(),
            ]);
        }

        // Attach resolved single bestPrice property for easy frontend access
        $products->getCollection()->transform(function ($prod) use ($market) {
            $prod->setRelation('bestPrice', $market ? $prod->bestPrices->firstWhere('market_id', $market->id) : $prod->bestPrices->first());
            return $prod;
        });

        return $this->paginated(ProductListResource::collection($products));
    }

    /**
     * Show canonical product detail with market offers and SEO schema
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $marketCode = $request->input('market', 'us');
        $market = Market::where('code', strtolower($marketCode))->first() 
            ?? Market::where('is_active', true)->first()
            ?? Market::first();

        $product = Product::where('slug', $slug)
            ->where('status', 'published')
            ->whereHas('category', function ($q) {
                $q->where('is_active', true);
            })
            ->with([
                'brand',
                'category',
                'primaryImage',
                'images',
                'specifications',
                'variants',
                'bestPrices' => function ($q) use ($market) {
                    if ($market) {
                        $q->where('market_id', $market->id)->with(['currency', 'bestOffer.retailer']);
                    }
                },
                'offers' => function ($q) use ($market) {
                    if ($market) {
                        $q->where('market_id', $market->id);
                    }
                    $q->where('is_active', true)
                      ->with(['retailer', 'currency', 'market'])
                      ->orderByRaw("CASE WHEN availability = 'in_stock' THEN 0 ELSE 1 END")
                      ->orderBy('price', 'asc');
                },
            ])
            ->first();

        if (!$product) {
            return $this->error('Product not found.', 404);
        }

        if ($market) {
            $product->setRelation('bestPrice', $product->bestPrices->firstWhere('market_id', $market->id));
        }

        $seoMeta = $market ? $this->metadataService->getProductMetadata($product, $market) : [];
        $structuredData = $market ? $this->structuredDataService->generateProductSchema($product, $market) : [];

        return $this->success(new ProductDetailResource($product), null, 200, [
            'seo' => $seoMeta,
            'structured_data' => $structuredData,
        ]);
    }

    /**
     * Compare up to 4 products side by side
     */
    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slugs' => ['required', 'string'],
            'market' => ['nullable', 'string'],
        ]);

        $slugs = array_filter(explode(',', $validated['slugs']));
        if (count($slugs) < 2 || count($slugs) > 4) {
            return $this->error('Comparison requires between 2 and 4 product slugs.', 422);
        }

        $marketCode = $validated['market'] ?? 'us';
        $market = Market::where('code', strtolower($marketCode))->first() 
            ?? Market::where('is_active', true)->first();

        $products = Product::whereIn('slug', $slugs)
            ->where('status', 'published')
            ->whereHas('category', function ($q) {
                $q->where('is_active', true);
            })
            ->with([
                'brand',
                'category',
                'primaryImage',
                'specifications',
                'bestPrices' => function ($q) use ($market) {
                    if ($market) {
                        $q->where('market_id', $market->id);
                    }
                    $q->with('currency');
                },
            ])
            ->get();

        $products->transform(function ($prod) use ($market) {
            $prod->setRelation('bestPrice', $market ? $prod->bestPrices->firstWhere('market_id', $market->id) : $prod->bestPrices->first());
            return $prod;
        });

        return $this->success(ProductDetailResource::collection($products));
    }
}

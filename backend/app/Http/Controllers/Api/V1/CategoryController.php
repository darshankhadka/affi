<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends BaseApiController
{
    /**
     * List categories with optional hierarchy
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('display_order');
            }])
            ->withCount(['products' => function ($q) {
                $q->where('status', 'published');
            }])
            ->orderBy('display_order');

        if ($request->boolean('root_only', true)) {
            $query->whereNull('parent_id');
        }

        $categories = $query->get();
        return $this->success(CategoryResource::collection($categories));
    }

    /**
     * Show category details by slug
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->with(['children', 'parent'])
            ->withCount(['products' => function ($q) {
                $q->where('status', 'published');
            }])
            ->first();

        if (!$category) {
            return $this->error('Category not found.', 404);
        }

        return $this->success(new CategoryResource($category));
    }

    /**
     * Get paginated products for a category
     */
    public function products(Request $request, string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)->first();
        if (!$category) {
            return $this->error('Category not found.', 404);
        }

        $request->merge(['category' => $category->slug]);
        return app(ProductController::class)->index($request);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends BaseApiController
{
    /**
     * List active brands
     */
    public function index(Request $request): JsonResponse
    {
        $brands = Brand::where('is_active', true)
            ->withCount(['products' => function ($q) {
                $q->where('status', 'published');
            }])
            ->orderBy('name')
            ->get();

        return $this->success(BrandResource::collection($brands));
    }

    /**
     * Show brand by slug
     */
    public function show(string $slug): JsonResponse
    {
        $brand = Brand::where('slug', $slug)
            ->where('is_active', true)
            ->withCount(['products' => function ($q) {
                $q->where('status', 'published');
            }])
            ->first();

        if (!$brand) {
            return $this->error('Brand not found.', 404);
        }

        return $this->success(new BrandResource($brand));
    }
}

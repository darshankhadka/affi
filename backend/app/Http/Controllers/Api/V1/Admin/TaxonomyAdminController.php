<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Brand;
use App\Models\Category;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaxonomyAdminController extends BaseApiController
{
    public function __construct(protected AuditLoggerService $auditLogger)
    {
    }

    // ----------------------------------------------------
    // CATEGORIES CRUD
    // ----------------------------------------------------

    public function categories(Request $request): JsonResponse
    {
        $categories = Category::withCount('products')
            ->orderBy('name')
            ->get();

        return $this->success($categories);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:categories,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = Category::create($validated);
        $this->auditLogger->log('category.create', $category, null, $category->toArray());

        return $this->success($category, 'Category created successfully.', 201);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['sometimes', 'required', 'string', 'max:120', "unique:categories,slug,{$id}"],
            'description' => ['nullable', 'string', 'max:500'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ]);

        $old = $category->toArray();
        $category->update($validated);
        $this->auditLogger->log('category.update', $category, $old, $category->toArray());

        return $this->success($category, 'Category updated successfully.');
    }

    public function destroyCategory(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        if ($category->products()->exists()) {
            return $this->error('Cannot delete category with associated products. Reassign products first.', 422);
        }

        $old = $category->toArray();
        $category->delete();
        $this->auditLogger->log('category.delete', $category, $old, null);

        return $this->success(null, 'Category deleted successfully.');
    }

    // ----------------------------------------------------
    // BRANDS CRUD
    // ----------------------------------------------------

    public function brands(Request $request): JsonResponse
    {
        $brands = Brand::withCount('products')
            ->orderBy('name')
            ->get();

        return $this->success($brands);
    }

    public function storeBrand(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:brands,slug'],
            'website' => ['nullable', 'url', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $brand = Brand::create($validated);
        $this->auditLogger->log('brand.create', $brand, null, $brand->toArray());

        return $this->success($brand, 'Brand created successfully.', 201);
    }

    public function updateBrand(Request $request, int $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['sometimes', 'required', 'string', 'max:120', "unique:brands,slug,{$id}"],
            'website' => ['nullable', 'url', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $old = $brand->toArray();
        $brand->update($validated);
        $this->auditLogger->log('brand.update', $brand, $old, $brand->toArray());

        return $this->success($brand, 'Brand updated successfully.');
    }

    public function destroyBrand(int $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);

        if ($brand->products()->exists()) {
            return $this->error('Cannot delete brand with associated products.', 422);
        }

        $old = $brand->toArray();
        $brand->delete();
        $this->auditLogger->log('brand.delete', $brand, $old, null);

        return $this->success(null, 'Brand deleted successfully.');
    }
}

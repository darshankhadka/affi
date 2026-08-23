<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'model_number' => $this->model_number,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'status' => $this->status,
            'release_date' => $this->release_date?->format('Y-m-d'),
            'canonical_upc' => $this->canonical_upc,
            'canonical_ean' => $this->canonical_ean,
            'canonical_mpn' => $this->canonical_mpn,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'primary_image' => $this->primaryImage ? [
                'url' => $this->primaryImage->url,
                'alt_text' => $this->primaryImage->alt_text,
            ] : null,
            'images' => $this->images->map(function ($img) {
                return [
                    'id' => $img->id,
                    'url' => $img->url,
                    'alt_text' => $img->alt_text,
                    'is_primary' => (bool) $img->is_primary,
                    'display_order' => $img->display_order,
                ];
            }),
            'specifications' => $this->specifications->groupBy('group_name')->map(function ($specs, $group) {
                return [
                    'group' => $group,
                    'items' => $specs->map(function ($s) {
                        return [
                            'name' => $s->spec_name,
                            'value' => $s->spec_value,
                        ];
                    })->values(),
                ];
            })->values(),
            'variants' => $this->variants->map(function ($v) {
                return [
                    'id' => $v->id,
                    'name' => $v->name,
                    'sku' => $v->sku,
                    'attributes' => $v->attributes,
                ];
            }),
            'best_price' => new BestPriceResource($this->whenLoaded('bestPrice')),
            'offers' => OfferResource::collection($this->whenLoaded('offers')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

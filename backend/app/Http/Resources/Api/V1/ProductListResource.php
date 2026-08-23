<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'model_number' => $this->model_number,
            'short_description' => $this->short_description,
            'status' => $this->status,
            'release_date' => $this->release_date?->format('Y-m-d'),
            'primary_image' => $this->primaryImage ? [
                'url' => $this->primaryImage->url,
                'alt_text' => $this->primaryImage->alt_text,
            ] : null,
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'best_price' => new BestPriceResource($this->whenLoaded('bestPrice')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

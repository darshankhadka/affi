<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_url' => $this->logo_url,
            'website_url' => $this->website_url,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'products_count' => $this->whenCounted('products'),
        ];
    }
}

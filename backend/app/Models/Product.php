<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'slug',
        'model_number',
        'description',
        'short_description',
        'status',
        'release_date',
        'canonical_upc',
        'canonical_ean',
        'canonical_mpn',
        'primary_image_id',
    ];

    protected $casts = [
        'release_date' => 'date',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function primaryImage()
    {
        return $this->belongsTo(ProductImage::class, 'primary_image_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('display_order');
    }

    public function specifications()
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('display_order');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function identifiers()
    {
        return $this->hasMany(ProductIdentifier::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function bestPrices()
    {
        return $this->hasMany(BestPrice::class);
    }

    public function priceHistory()
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'variant_id',
        'retailer_id',
        'market_id',
        'currency_id',
        'sku',
        'title',
        'affiliate_url',
        'original_url',
        'price',
        'original_price',
        'discount_percentage',
        'shipping_cost',
        'availability',
        'condition',
        'is_active',
        'last_checked_at',
        'next_check_at',
        'error_count',
    ];

    protected $casts = [
        'price' => 'float',
        'original_price' => 'float',
        'discount_percentage' => 'float',
        'shipping_cost' => 'float',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'next_check_at' => 'datetime',
        'error_count' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function retailer()
    {
        return $this->belongsTo(Retailer::class);
    }

    public function market()
    {
        return $this->belongsTo(Market::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function priceHistory()
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function clicks()
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('availability', 'in_stock');
    }

    public function scopeForMarket($query, $marketId)
    {
        return $query->where('market_id', $marketId);
    }
}

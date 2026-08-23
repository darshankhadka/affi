<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BestPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'market_id',
        'currency_id',
        'min_price',
        'max_price',
        'best_offer_id',
        'offer_count',
        'in_stock_offer_count',
    ];

    protected $casts = [
        'min_price' => 'float',
        'max_price' => 'float',
        'offer_count' => 'integer',
        'in_stock_offer_count' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function market()
    {
        return $this->belongsTo(Market::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function bestOffer()
    {
        return $this->belongsTo(Offer::class, 'best_offer_id');
    }
}

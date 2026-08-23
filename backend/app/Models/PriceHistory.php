<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    use HasFactory;

    protected $table = 'price_history';

    protected $fillable = [
        'offer_id',
        'product_id',
        'market_id',
        'currency_id',
        'price',
        'original_price',
        'availability',
        'recorded_at',
    ];

    protected $casts = [
        'price' => 'float',
        'original_price' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

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
}

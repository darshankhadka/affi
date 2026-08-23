<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'default_currency_id',
        'locale',
        'hreflang',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function defaultCurrency()
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    public function countries()
    {
        return $this->hasMany(Country::class);
    }

    public function affiliateAccounts()
    {
        return $this->hasMany(AffiliateAccount::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function bestPrices()
    {
        return $this->hasMany(BestPrice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

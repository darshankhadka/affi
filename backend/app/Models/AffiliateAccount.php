<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'market_id',
        'retailer_id',
        'account_tag',
        'credentials',
        'status',
        'is_active',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array',
        'is_active' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(AffiliateProvider::class, 'provider_id');
    }

    public function market()
    {
        return $this->belongsTo(Market::class);
    }

    public function retailer()
    {
        return $this->belongsTo(Retailer::class);
    }
}

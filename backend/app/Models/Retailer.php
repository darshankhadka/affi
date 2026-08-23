<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Retailer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'logo_url',
        'affiliate_provider_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function affiliateProvider()
    {
        return $this->belongsTo(AffiliateProvider::class, 'affiliate_provider_id');
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function clicks()
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

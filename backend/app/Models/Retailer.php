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
        'code',
        'domain',
        'country',
        'market_code',
        'currency_code',
        'logo_url',
        'website_url',
        'terms_url',
        'affiliate_provider_id',
        'affiliate_network',
        'affiliate_program_id',
        'programme_id',
        'status',
        'integration_type',
        'api_available',
        'feed_available',
        'deep_link_supported',
        'price_tracking_supported',
        'is_active',
        'last_successful_sync_at',
        'last_failed_sync_at',
        'last_error',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_available' => 'boolean',
        'feed_available' => 'boolean',
        'deep_link_supported' => 'boolean',
        'price_tracking_supported' => 'boolean',
        'last_successful_sync_at' => 'datetime',
        'last_failed_sync_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function affiliateProvider()
    {
        return $this->belongsTo(AffiliateProvider::class, 'affiliate_provider_id');
    }

    /**
     * The approved programme record for this retailer's affiliate relationship.
     * Check $retailer->programme->isApproved() before promoting any offer.
     */
    public function programme()
    {
        return $this->belongsTo(AffiliateProgramme::class, 'programme_id');
    }

    public function affiliateAccounts()
    {
        return $this->hasMany(AffiliateAccount::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function clicks()
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function market()
    {
        return $this->belongsTo(Market::class, 'market_code', 'code');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isConfigured(): bool
    {
        return in_array($this->status, ['connected', 'approved', 'pending_approval']);
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }
}

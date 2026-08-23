<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'is_active',
        'config',
        'rate_limit_per_minute',
        'status',
        'last_sync_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'encrypted:array',
        'rate_limit_per_minute' => 'integer',
        'last_sync_at' => 'datetime',
    ];

    public function accounts()
    {
        return $this->hasMany(AffiliateAccount::class, 'provider_id');
    }

    public function retailers()
    {
        return $this->hasMany(Retailer::class);
    }

    public function offers()
    {
        return $this->hasManyThrough(Offer::class, Retailer::class);
    }

    public function automationJobs()
    {
        return $this->hasMany(AutomationJob::class, 'provider_id');
    }
}

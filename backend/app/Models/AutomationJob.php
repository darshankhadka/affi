<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutomationJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'market_id',
        'batch_type',
        'status',
        'total_items',
        'processed_items',
        'failed_items',
        'started_at',
        'finished_at',
        'memory_peak_bytes',
        'cpu_time_ms',
        'error_log',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'total_items' => 'integer',
        'processed_items' => 'integer',
        'failed_items' => 'integer',
        'memory_peak_bytes' => 'integer',
        'cpu_time_ms' => 'integer',
        'metadata' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(AffiliateProvider::class, 'provider_id');
    }

    public function market()
    {
        return $this->belongsTo(Market::class);
    }
}

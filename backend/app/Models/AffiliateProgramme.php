<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AffiliateProgramme — the canonical approval record for a publisher ↔ advertiser relationship.
 *
 * A programme is the network-specific approved partnership. Retailers and offers
 * must only be promoted if the programme status is 'approved'.
 *
 * Status lifecycle:
 *   pending   → the publisher has applied but not yet been accepted/rejected
 *   approved  → the advertiser has accepted the publisher; promotion is permitted
 *   rejected  → the advertiser rejected the application (e.g. Dell ANZ rejection)
 *   suspended → temporarily paused by advertiser or network
 *   expired   → the programme has ended
 *   inactive  → manually deactivated by the publisher
 */
class AffiliateProgramme extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'external_programme_id',
        'name',
        'publisher_id',
        'status',
        'approved_at',
        'rejected_at',
        'commission_type',
        'commission_value',
        'currency',
        'cookie_duration_days',
        'epc',
        'conversion_rate',
        'metrics_updated_at',
        'network_metadata',
        'last_synced_at',
    ];

    protected $casts = [
        'approved_at'          => 'datetime',
        'rejected_at'          => 'datetime',
        'metrics_updated_at'   => 'datetime',
        'last_synced_at'       => 'datetime',
        'commission_value'     => 'float',
        'epc'                  => 'float',
        'conversion_rate'      => 'float',
        'cookie_duration_days' => 'integer',
        'network_metadata'     => 'array',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AffiliateProvider::class, 'provider_id');
    }

    public function retailers(): HasMany
    {
        return $this->hasMany(Retailer::class, 'programme_id');
    }

    // -------------------------------------------------------------------------
    // Approval Status Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns true only when the programme is explicitly approved.
     * This is the gate that must pass before any offer can be promoted.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Returns true if the programme permits normal monetization.
     * Only 'approved' qualifies. All other statuses block promotion.
     */
    public function isPromotable(): bool
    {
        return $this->isApproved();
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForProvider($query, $providerCode)
    {
        return $query->whereHas('provider', fn ($q) => $q->where('code', $providerCode));
    }

    public function scopeByExternalId($query, string $externalId)
    {
        return $query->where('external_programme_id', $externalId);
    }
}

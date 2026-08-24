<?php

namespace App\Services\Ingestion;

use App\DTOs\RetailerIdentityInput;
use App\Models\AffiliateProvider;
use App\Models\Market;
use App\Models\Retailer;
use App\Support\SecretRedactor;
use Illuminate\Support\Str;

/**
 * Resolves a canonical Retailer record from feed-derived identity, with stable
 * deduplication across domain/subdomain/TLD formatting and merchant-name churn.
 *
 * Identity precedence (most stable first):
 *   1. affiliate_provider_id + affiliate_program_id (Awin advertiser id)
 *   2. canonical_domain (root/registrable domain, e.g. "blazevideos.de")
 *   3. exact domain match
 *
 * The `code` and `slug` are derived from the stable external identity so they do
 * NOT change when a feed renames a merchant or reformats its domain.
 */
class RetailerIdentityService
{
    /**
     * Resolve an existing retailer or create one with a stable identity.
     */
    public function resolveOrCreate(RetailerIdentityInput $input): Retailer
    {
        $existing = $this->findExisting($input);
        if ($existing) {
            $this->enrich($existing, $input);
            return $existing;
        }

        return $this->create($input);
    }

    public function findExisting(RetailerIdentityInput $input): ?Retailer
    {
        // 1. Provider + advertiser/programme id is the strongest identity.
        if ($input->providerId && $input->advertiserId) {
            $byProgram = Retailer::where('affiliate_provider_id', $input->providerId)
                ->where('affiliate_program_id', (string) $input->advertiserId)
                ->first();
            if ($byProgram) {
                return $byProgram;
            }
        }

        // 2. Canonical (root) domain — scoped to market so that a shared root domain
        //    (e.g. amazon.de for AT/DE/LU) maps to the correct per-market retailer.
        $canonical = $this->canonicalDomain($input->domain);
        if ($canonical) {
            $q = Retailer::where('canonical_domain', $canonical);
            if ($input->marketCode) {
                $q->where('market_code', strtolower($input->marketCode));
            }
            $byCanonical = $q->first();
            if ($byCanonical) {
                return $byCanonical;
            }

            $q2 = Retailer::where('domain', $canonical);
            if ($input->marketCode) {
                $q2->where('market_code', strtolower($input->marketCode));
            }
            $byDomain = $q2->first();
            if ($byDomain) {
                return $byDomain;
            }
        }

        // 3. Exact raw domain (market-scoped for the same reason).
        if ($input->domain) {
            $q3 = Retailer::where('domain', $this->normalizeDomain($input->domain));
            if ($input->marketCode) {
                $q3->where('market_code', strtolower($input->marketCode));
            }
            $byRaw = $q3->first();
            if ($byRaw) {
                return $byRaw;
            }
        }

        return null;
    }

    protected function create(RetailerIdentityInput $input): Retailer
    {
        $canonical = $this->canonicalDomain($input->domain);
        $domain = $canonical ?? $this->normalizeDomain($input->domain) ?? Str::slug($input->name) . '.com';

        $code = $this->deriveCode($input);
        $slug = $this->uniqueSlug($code);

        $provider = $input->providerId
            ? AffiliateProvider::find($input->providerId)
            : AffiliateProvider::where('code', $input->providerCode)->first();

        $market = $input->marketCode ? Market::where('code', strtolower($input->marketCode))->first() : null;
        $country = $market ? strtoupper($market->code) : ($input->marketCode ? strtoupper($input->marketCode) : null);

        return Retailer::create([
            'name' => $input->name ?: $domain,
            'slug' => $slug,
            'code' => $code,
            'domain' => $domain,
            'canonical_domain' => $canonical,
            'country' => $country,
            'market_code' => $market?->code,
            'currency_code' => $input->currencyCode,
            'website_url' => $input->websiteUrl,
            'affiliate_provider_id' => $provider?->id,
            'affiliate_network' => $provider?->code,
            'affiliate_program_id' => $input->advertiserId,
            'status' => 'connected',
            'integration_type' => 'affiliate_network',
            'is_active' => true,
        ]);
    }

    protected function enrich(Retailer $retailer, RetailerIdentityInput $input): void
    {
        $dirty = false;

        // Keep advertiser id / programme linkage once known (never overwrite with null).
        if (empty($retailer->affiliate_program_id) && $input->advertiserId) {
            $retailer->affiliate_program_id = $input->advertiserId;
            $dirty = true;
        }

        // Backfill canonical domain once known.
        $canonical = $this->canonicalDomain($input->domain);
        if (empty($retailer->canonical_domain) && $canonical) {
            $retailer->canonical_domain = $canonical;
            $dirty = true;
        }

        // Prefer a more specific domain if the existing one is generic.
        if (empty($retailer->domain) && $input->domain) {
            $retailer->domain = $canonical ?? $this->normalizeDomain($input->domain);
            $dirty = true;
        }

        if ($dirty) {
            $retailer->saveQuietly();
        }
    }

    /**
     * Derive a STABLE code. Prefer the advertiser/programme id; fall back to the
     * canonical domain slug. Never uses an md5() hash (which churns on reformat).
     */
    protected function deriveCode(RetailerIdentityInput $input): string
    {
        if ($input->providerCode && $input->advertiserId) {
            return "{$input->providerCode}-{$input->advertiserId}";
        }
        if ($input->advertiserId) {
            return "adv-{$input->advertiserId}";
        }

        $canonical = $this->canonicalDomain($input->domain);
        if ($canonical) {
            return Str::slug($canonical);
        }

        return Str::slug($input->name) ?: 'retailer';
    }

    /**
     * Generate a slug unique within the retailers table.
     */
    protected function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'retailer';
        if (!Retailer::where('slug', $slug)->exists()) {
            return $slug;
        }

        $i = 1;
        do {
            $candidate = $slug . '-' . $i++;
        } while (Retailer::where('slug', $candidate)->exists());

        return $candidate;
    }

    /**
     * Normalize a raw domain: lowercase, strip scheme, "www." and port.
     */
    public function normalizeDomain(?string $domain): ?string
    {
        if (empty($domain)) {
            return null;
        }

        $host = preg_replace('#^https?://#i', '', trim($domain));
        $host = preg_replace('#/.*$#', '', $host);
        $host = preg_replace('/:\d+$/', '', strtolower(trim($host)));
        $host = preg_replace('/^www\./i', '', $host);

        return $host ?: null;
    }

    /**
     * Registrable (root) domain: eTLD+1, handling common multi-level TLDs.
     * "shop.example.de" and "www.example.de" both -> "example.de".
     */
    public function canonicalDomain(?string $domain): ?string
    {
        $host = $this->normalizeDomain($domain);
        if (empty($host) || !str_contains($host, '.')) {
            return $host;
        }

        $parts = explode('.', $host);
        $count = count($parts);

        $multiLevelTlds = ['co', 'com', 'net', 'org', 'gov', 'ac', 'edu'];
        // e.g. example.co.uk -> last 3 labels
        if ($count >= 3 && in_array($parts[$count - 2], $multiLevelTlds, true)) {
            return implode('.', array_slice($parts, -3));
        }

        return implode('.', array_slice($parts, -2));
    }
}

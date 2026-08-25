<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AffiliateClick;
use App\Models\Offer;
use App\Services\Affiliate\AffiliateRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AffiliateClickController extends BaseApiController
{
    public function __construct(protected AffiliateRegistry $registry)
    {
    }

    /**
     * Record click and redirect to monetized affiliate destination URL.
     *
     * Validation pipeline (must all pass before redirect):
     *   1. Offer exists and is active.
     *   2. Programme is approved (when a programme record is linked).
     *   3. Tracking URL is resolved via the provider adapter — never trusted from user input.
     */
    public function out(Request $request, int $offerId): RedirectResponse|JsonResponse
    {
        $offer = Offer::with([
            'retailer.affiliateProvider',
            'retailer.programme',
            'market',
            'product',
        ])->find($offerId);

        if (!$offer || !$offer->is_active) {
            if ($request->wantsJson()) {
                return $this->error('The selected retailer offer is currently unavailable.', 404);
            }
            abort(404, 'The selected retailer offer is currently unavailable.');
        }

        // Programme approval gate.
        // If the retailer has an explicit programme record, it must be approved.
        // If no programme record is linked (e.g. Amazon, legacy retailers), the offer
        // is allowed through — the is_active flag is sufficient.
        $programme = $offer->retailer?->programme;
        if ($programme !== null && !$programme->isApproved()) {
            Log::warning('Affiliate redirect blocked: programme not approved', [
                'offer_id'              => $offer->id,
                'programme_id'          => $programme->id,
                'external_programme_id' => $programme->external_programme_id,
                'programme_status'      => $programme->status,
                'retailer'              => $offer->retailer?->name,
            ]);

            if ($request->wantsJson()) {
                return $this->error('This affiliate offer is currently not available for promotion.', 403);
            }
            return redirect(config('app.url', '/'));
        }

        // Generate privacy-safe IP hash
        $ipHash = hash('sha256', $request->ip() . config('app.key'));

        // Record click event
        AffiliateClick::create([
            'offer_id'   => $offer->id,
            'product_id' => $offer->product_id,
            'retailer_id' => $offer->retailer_id,
            'market_id'  => $offer->market_id,
            'referrer'   => substr((string) $request->header('referer'), 0, 500),
            'user_agent' => $request->userAgent(),
            'ip_hash'    => $ipHash,
            'session_id' => $request->input('sid') ?? session()->getId(),
            'clicked_at' => now(),
        ]);

        // Resolve monetized deep link via provider adapter.
        // The frontend never constructs provider-specific URLs — they always come
        // from the provider adapter registered in AffiliateRegistry.
        $targetUrl = $offer->affiliate_url;
        $provider  = $offer->retailer?->affiliateProvider;

        if ($provider && $this->registry->has($provider->code)) {
            $connector = $this->registry->get($provider->code);
            $targetUrl = $connector->generateAffiliateUrl($offer, $offer->market, $request->input('subid'));
        }

        if (empty($targetUrl)) {
            $targetUrl = $offer->original_url ?: config('app.url', '/');
        }

        if ($request->wantsJson()) {
            return $this->success(['url' => $targetUrl], 'Affiliate redirect resolved.');
        }

        // Return 302 Found redirect with security headers appropriate for affiliate links.
        return redirect()->away($targetUrl, 302, [
            'Cache-Control'   => 'no-cache, no-store, must-revalidate',
            'Pragma'          => 'no-cache',
            'Expires'         => '0',
            'Referrer-Policy' => 'no-referrer-when-downgrade',
            'X-Robots-Tag'    => 'noindex, nofollow',
        ]);
    }
}

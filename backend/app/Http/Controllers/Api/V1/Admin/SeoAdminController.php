<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Market;
use App\Models\Redirect;
use App\Services\Audit\AuditLoggerService;
use App\Services\SEO\SitemapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoAdminController extends BaseApiController
{
    public function __construct(
        protected SitemapService $sitemapService,
        protected AuditLoggerService $auditLogger
    ) {
    }

    /**
     * Get SEO overview
     */
    public function overview(): JsonResponse
    {
        return $this->success([
            'sitemap_index_url' => config('app.url') . '/sitemap.xml',
            'robots_txt_url' => config('app.url') . '/robots.txt',
            'active_markets' => Market::where('is_active', true)->pluck('code'),
            'total_redirects' => Redirect::count(),
        ]);
    }

    /**
     * List all redirects
     */
    public function redirects(): JsonResponse
    {
        $redirects = Redirect::latest()->paginate(50);
        return $this->success($redirects);
    }

    /**
     * Store a redirect rule
     */
    public function storeRedirect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_path' => ['required', 'string', 'unique:redirects,source_path'],
            'target_path' => ['required', 'string'],
            'status_code' => ['required', 'in:301,302,307,308'],
            'is_active' => ['boolean'],
        ]);

        $redirect = Redirect::create($validated);
        $this->auditLogger->log('redirect.create', $redirect, null, $redirect->toArray());

        return $this->success($redirect, 'Redirect created.', 201);
    }

    /**
     * Delete a redirect rule
     */
    public function destroyRedirect(int $id): JsonResponse
    {
        $redirect = Redirect::findOrFail($id);
        $this->auditLogger->log('redirect.delete', $redirect, $redirect->toArray(), null);
        $redirect->delete();

        return $this->success(null, 'Redirect deleted.');
    }
}

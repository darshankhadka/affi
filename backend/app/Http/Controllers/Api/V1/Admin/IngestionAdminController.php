<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationJob;
use App\Services\Ingestion\BulkIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class IngestionAdminController extends Controller
{
    public function __construct(
        protected BulkIngestionService $bulkIngestionService
    ) {}

    /**
     * Get live catalog metrics and recent ingestion jobs.
     */
    public function status(): JsonResponse
    {
        $metrics = $this->bulkIngestionService->getCatalogMetrics();
        return response()->json([
            'success' => true,
            'data' => $metrics,
            'message' => 'Catalog and ingestion status retrieved.',
        ]);
    }

    /**
     * Trigger bulk ingestion for CJ or Awin.
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:cj,awin',
            'market' => 'nullable|string',
            'max_products' => 'nullable|integer|min:1|max:500',
            'batch_size' => 'nullable|integer|min:1|max:50',
            'partner_id' => 'nullable|string',
            'advertiser_id' => 'nullable|integer',
            'keywords' => 'nullable|string',
            'dry_run' => 'nullable|boolean',
            'resume' => 'nullable|boolean',
        ]);

        $providerCode = strtolower($validated['provider']);

        try {
            if ($providerCode === 'cj') {
                $result = $this->bulkIngestionService->ingestCjBulk($validated);
            } elseif ($providerCode === 'awin') {
                $result = $this->bulkIngestionService->ingestAwinBulk($validated);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Unsupported provider {$providerCode}",
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Bulk ingestion completed for {$providerCode}.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Ingestion failed: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Get details for an automation job.
     */
    public function jobDetails(int $id): JsonResponse
    {
        $job = AutomationJob::with(['provider', 'market'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $job,
            'message' => 'Job details retrieved.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\AutomationJob;
use App\Services\Automation\CpuSafeIngestionOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutomationAdminController extends BaseApiController
{
    public function __construct(protected CpuSafeIngestionOrchestrator $orchestrator)
    {
    }

    /**
     * List automation jobs and batch history
     */
    public function jobs(Request $request): JsonResponse
    {
        $jobs = AutomationJob::with(['provider', 'market'])
            ->latest()
            ->paginate(30);

        return $this->success($jobs);
    }

    /**
     * Trigger a manual, CPU-safe batch execution
     */
    public function triggerBatch(Request $request): JsonResponse
    {
        $result = $this->orchestrator->runPriceRefreshBatch();

        return $this->success($result, 'Bounded price refresh batch completed.');
    }
}

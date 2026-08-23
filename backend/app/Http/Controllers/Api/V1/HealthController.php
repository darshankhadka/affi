<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Health\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthController extends BaseApiController
{
    public function __construct(protected HealthCheckService $healthService)
    {
    }

    /**
     * System health status check endpoint
     */
    public function check(): JsonResponse
    {
        $status = $this->healthService->check();
        $code = $status['status'] === 'healthy' ? 200 : 503;

        return $this->success($status, 'System health report', $code);
    }
}

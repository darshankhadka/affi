<?php

namespace Tests\Feature;

use App\Models\AutomationJob;
use App\Services\Automation\CpuSafeIngestionOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CpuSafeAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('system:init-foundation');
    }

    public function test_cpu_safe_batch_execution_completes_safely_and_creates_job_record(): void
    {
        $orchestrator = app(CpuSafeIngestionOrchestrator::class);
        $result = $orchestrator->runPriceRefreshBatch();

        $this->assertContains($result['status'], ['completed', 'skipped']);
        $this->assertGreaterThanOrEqual(0, $result['processed']);
        $this->assertGreaterThan(0.0, $result['memory_peak_mb']);

        if ($result['job_id']) {
            $this->assertDatabaseHas('automation_jobs', [
                'id' => $result['job_id'],
                'batch_type' => 'price_refresh',
            ]);
        }
    }
}

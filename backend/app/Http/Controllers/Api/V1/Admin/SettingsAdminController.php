<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Setting;
use App\Services\Audit\AuditLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsAdminController extends BaseApiController
{
    public function __construct(protected AuditLoggerService $auditLogger)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key');
        return $this->success($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $updated = [];
        foreach ($validated['settings'] as $key => $value) {
            $setting = Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : (string)$value]
            );
            $updated[$key] = $setting->value;
        }

        $this->auditLogger->log('settings.update', null, null, $updated);

        return $this->success($updated, 'Settings updated successfully.');
    }
}

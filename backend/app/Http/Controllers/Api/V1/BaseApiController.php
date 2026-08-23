<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BaseApiController extends Controller
{
    /**
     * Return a standardized success JSON response
     */
    protected function success(mixed $data = null, ?string $message = null, int $statusCode = 200, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a standardized error JSON response
     */
    protected function error(string $message, int $statusCode = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a standardized paginated JSON response
     */
    protected function paginated(AnonymousResourceCollection $collection, ?string $message = null): JsonResponse
    {
        $resourceArray = $collection->response()->getData(true);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resourceArray['data'],
            'meta' => $resourceArray['meta'] ?? [
                'current_page' => $resourceArray['current_page'] ?? 1,
                'last_page' => $resourceArray['last_page'] ?? 1,
                'per_page' => $resourceArray['per_page'] ?? 20,
                'total' => $resourceArray['total'] ?? count($resourceArray['data']),
            ],
        ]);
    }
}

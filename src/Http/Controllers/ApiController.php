<?php

declare(strict_types=1);

namespace Nutandc\ApiCrud\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;

abstract class ApiController extends Controller
{
    protected function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function error(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    protected function respondWithResource(JsonResource $resource, ?string $message = null, int $status = 200): JsonResponse
    {
        return $this->success($resource->resolve(request()), $message, $status);
    }

    protected function respondWithCollection(ResourceCollection $collection, ?string $message = null, int $status = 200): JsonResponse
    {
        $payload = $collection->response()->getData(true);

        return $this->success($payload, $message, $status);
    }
}

<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * The single place the v1 API decides what a response looks like.
 *
 * Every body carries a boolean "success". Success bodies add "data" (and "meta"
 * plus "links" when paginated); failure bodies add "message", "errors" for
 * field-level validation problems and "error_code" for domain rule violations.
 * Clients can therefore branch on shape without inspecting the endpoint called.
 */
class ApiResponse
{
    /**
     * @param  array<string, mixed>|JsonResource|ResourceCollection|null  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data, int $status = 200, array $meta = [], ?string $message = null): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $data,
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * Wraps a paginator in the documented envelope. Keeping this in one place
     * stops each controller from hand-rolling its own meta block.
     *
     * @param  array<int, mixed>|JsonResource|ResourceCollection  $data
     * @param  array<string, mixed>  $extraMeta  Endpoint-specific meta merged alongside the pagination keys.
     */
    public static function paginated(mixed $data, LengthAwarePaginator $paginator, array $extraMeta = [], ?string $message = null): JsonResponse
    {
        $payload = [
            'success' => true,
            'data' => $data,
            'meta' => array_merge([
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ], $extraMeta),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        return response()->json($payload);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function error(string $message, int $status = 400, ?string $errorCode = null, array $extra = []): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorCode !== null) {
            $payload['error_code'] = $errorCode;
        }

        return response()->json(array_merge($payload, $extra), $status);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}

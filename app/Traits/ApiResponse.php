<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Respuesta JSON exitosa estándar.
     *
     * @param  array<string, mixed>  $meta
     */
    protected function successResponse(
        string $message = 'Operación realizada correctamente.',
        mixed $data = null,
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'meta' => array_merge($this->defaultMeta(), $meta),
        ], $statusCode);
    }

    /**
     * Respuesta JSON de error estándar.
     *
     * @param  array<string, mixed>|null  $errors
     * @param  array<string, mixed>  $meta
     */
    protected function errorResponse(
        string $message = 'Ocurrió un error.',
        int $statusCode = 400,
        mixed $errors = null,
        ?string $errorCode = null,
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => false,
            'status_code' => $statusCode,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
            'meta' => array_merge($this->defaultMeta(), $meta),
        ];

        if ($errorCode !== null) {
            $payload['error_code'] = $errorCode;
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * Respuesta JSON paginada estándar.
     *
     * @param  array<string, mixed>  $meta
     */
    protected function paginatedResponse(
        string $message,
        LengthAwarePaginator $paginator,
        mixed $data = null,
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $data ?? $paginator->items(),
            'errors' => null,
            'meta' => array_merge($this->defaultMeta(), $meta, [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ],
            ]),
        ], $statusCode);
    }

    /**
     * @return array<string, string>
     */
    private function defaultMeta(): array
    {
        return [
            'request_id' => request()->headers->get('X-Request-ID') ?? uniqid('req_'),
            'api_version' => 'v1',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

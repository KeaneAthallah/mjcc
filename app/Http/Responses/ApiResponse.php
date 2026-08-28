<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\MessageBag;

/**
 * Central, consistent JSON envelope for every API response.
 *
 * Success:  { success, message, data }
 * List:     { success, message, data, meta }
 * Error:    { success, false, message, errors? }
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $additional
     */
    public static function success(
        mixed $data = null,
        string $message = 'Data berhasil diambil',
        int $status = 200,
        array $additional = [],
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            ...$additional,
        ], $status);
    }

    /**
     * @param  mixed[]  $items
     */
    public static function paginate(
        array $items,
        LengthAwarePaginator $paginator,
        string $message = 'Data berhasil diambil',
    ): JsonResponse {
        return self::success($items, $message, 200, [
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function error(
        string $message,
        int $status = 400,
        array $errors = [],
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors ?: null,
        ], $status);
    }

    /**
     * Builds the standard 422 validation-error envelope.
     *
     * @param  MessageBag|array<string, mixed>  $errors
     */
    public static function validationErrors(mixed $errors): JsonResponse
    {
        return self::error('Validasi gagal', 422, (array) $errors);
    }
}

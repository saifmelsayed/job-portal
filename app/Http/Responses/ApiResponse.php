<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * Simple JSON shape for the API:
 * - Success: { "data": ... } or { "data": ..., "token": "..." } for auth
 * - Error / note: { "message": "..." }
 */
final class ApiResponse
{
    public static function data(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $payload], $status);
    }

    public static function dataWithToken(mixed $payload, string $token, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $payload,
            'token' => $token,
        ], $status);
    }

    public static function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}

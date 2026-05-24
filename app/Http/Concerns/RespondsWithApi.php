<?php

namespace App\Http\Concerns;

use Illuminate\Http\JsonResponse;

trait RespondsWithApi
{
    protected function successResponse(string $message = 'OK', mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data ?? (object) [],
            'errors' => null,
        ], $status);
    }

    protected function errorResponse(string $message, mixed $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors ?? (object) [],
        ], $status);
    }
}

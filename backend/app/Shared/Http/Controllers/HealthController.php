<?php

namespace App\Shared\Http\Controllers;

use Illuminate\Http\JsonResponse;

final class HealthController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'service' => 'eclesiapp-api',
                'status' => 'ok',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ]);
    }
}

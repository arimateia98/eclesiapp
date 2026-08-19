<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\ActiveParishController;
use App\Modules\Identity\Http\Controllers\MeController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function (Request $request): JsonResponse {
        $requestId = $request->header('X-Request-Id');

        if (! is_string($requestId) || $requestId === '') {
            $requestId = (string) Str::uuid();
        }

        return response()->json([
            'data' => ['status' => 'ok'],
            'meta' => ['request_id' => $requestId],
        ])->header('X-Request-Id', $requestId);
    })->name('api.v1.health');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', MeController::class)->name('api.v1.me');
        Route::get('/active-parish', [ActiveParishController::class, 'show'])
            ->middleware('active.parish')
            ->name('api.v1.active-parish.show');
    });
});

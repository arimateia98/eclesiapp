<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Controllers\ActiveParishController;
use App\Modules\Identity\Http\Controllers\MeController;
use App\Modules\PastoralOrganization\Http\Controllers\PastoralAreaController;
use App\Modules\PastoralOrganization\Http\Controllers\PastoralFunctionController;
use App\Modules\PastoralOrganization\Http\Controllers\ServantController;
use App\Modules\PastoralOrganization\Http\Controllers\ServantFunctionController;
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
        Route::middleware('active.parish')->prefix('/parishes/{parishId}')->group(function (): void {
            Route::get('/servants', [ServantController::class, 'index'])->name('api.v1.servants.index');
            Route::post('/servants', [ServantController::class, 'store'])->name('api.v1.servants.store');
            Route::patch('/servants/{servantId}', [ServantController::class, 'update'])->name('api.v1.servants.update');
            Route::post('/servants/{servantId}/functions', [ServantFunctionController::class, 'store'])
                ->name('api.v1.servant-functions.store');
            Route::get('/pastoral-areas', [PastoralAreaController::class, 'index'])->name('api.v1.pastoral-areas.index');
            Route::post('/pastoral-areas', [PastoralAreaController::class, 'store'])->name('api.v1.pastoral-areas.store');
            Route::get('/pastoral-functions', [PastoralFunctionController::class, 'index'])->name('api.v1.pastoral-functions.index');
            Route::post('/pastoral-functions', [PastoralFunctionController::class, 'store'])->name('api.v1.pastoral-functions.store');
        });
    });
});

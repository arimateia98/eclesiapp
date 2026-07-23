<?php

use App\Modules\Identity\Http\Controllers\AuthController;
use App\Modules\Identity\Http\Controllers\PersonProfileController;
use App\Modules\Organizations\Http\Controllers\OrganizationController;
use App\Modules\Organizations\Http\Controllers\OrganizationMemberController;
use App\Modules\Organizations\Http\Controllers\OrganizationRelationshipController;
use App\Shared\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', HealthController::class)->name('api.v1.health');

    Route::middleware('throttle:5,1')->group(function (): void {
        Route::post('/auth/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::delete('/auth/token', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        Route::get('/profile', [PersonProfileController::class, 'show'])->name('api.v1.profile.show');
        Route::post('/profile', [PersonProfileController::class, 'store'])->name('api.v1.profile.store');

        Route::get('/organizations', [OrganizationController::class, 'index'])->name('api.v1.organizations.index');
        Route::post('/organizations', [OrganizationController::class, 'store'])->name('api.v1.organizations.store');
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('api.v1.organizations.show');
        Route::post('/organizations/{organization}/members', [OrganizationMemberController::class, 'store'])
            ->name('api.v1.organizations.members.store');
        Route::post('/organizations/{organization}/relationships', [OrganizationRelationshipController::class, 'store'])
            ->name('api.v1.organizations.relationships.store');
    });
});

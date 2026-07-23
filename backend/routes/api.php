<?php

use App\Modules\Identity\Http\Controllers\AuthController;
use App\Modules\Identity\Http\Controllers\PersonAccountInvitationAcceptanceController;
use App\Modules\Identity\Http\Controllers\PersonAccountInvitationController;
use App\Modules\Identity\Http\Controllers\PersonProfileController;
use App\Modules\Ministries\Http\Controllers\MinistryTypeController;
use App\Modules\Ministries\Http\Controllers\PersonFunctionController;
use App\Modules\Ministries\Http\Controllers\ServiceFunctionController;
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
        Route::post('/auth/account-invitations/accept', [PersonAccountInvitationAcceptanceController::class, 'store'])
            ->name('api.v1.auth.account-invitations.accept');
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::delete('/auth/token', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        Route::get('/profile', [PersonProfileController::class, 'show'])->name('api.v1.profile.show');
        Route::post('/profile', [PersonProfileController::class, 'store'])->name('api.v1.profile.store');

        Route::get('/organizations', [OrganizationController::class, 'index'])->name('api.v1.organizations.index');
        Route::post('/organizations', [OrganizationController::class, 'store'])->name('api.v1.organizations.store');
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('api.v1.organizations.show');
        Route::get('/organizations/{organization}/members', [OrganizationMemberController::class, 'index'])
            ->name('api.v1.organizations.members.index');
        Route::post('/organizations/{organization}/members', [OrganizationMemberController::class, 'store'])
            ->name('api.v1.organizations.members.store');
        Route::get('/organizations/{organization}/ministry-types', [MinistryTypeController::class, 'index'])
            ->name('api.v1.organizations.ministry-types.index');
        Route::post('/organizations/{organization}/ministry-types', [MinistryTypeController::class, 'store'])
            ->name('api.v1.organizations.ministry-types.store');
        Route::get('/organizations/{organization}/service-functions', [ServiceFunctionController::class, 'index'])
            ->name('api.v1.organizations.service-functions.index');
        Route::post('/organizations/{organization}/service-functions', [ServiceFunctionController::class, 'store'])
            ->name('api.v1.organizations.service-functions.store');
        Route::get(
            '/organizations/{organization}/members/{person}/functions',
            [PersonFunctionController::class, 'index'],
        )->name('api.v1.organizations.members.functions.index');
        Route::post(
            '/organizations/{organization}/members/{person}/functions',
            [PersonFunctionController::class, 'store'],
        )->name('api.v1.organizations.members.functions.store');
        Route::delete(
            '/organizations/{organization}/members/{person}/functions/{serviceFunction}',
            [PersonFunctionController::class, 'destroy'],
        )->name('api.v1.organizations.members.functions.destroy');
        Route::post(
            '/organizations/{organization}/members/{person}/account-invitations',
            [PersonAccountInvitationController::class, 'store'],
        )->name('api.v1.organizations.members.account-invitations.store');
        Route::post('/organizations/{organization}/relationships', [OrganizationRelationshipController::class, 'store'])
            ->name('api.v1.organizations.relationships.store');
    });
});

<?php

use App\Modules\Identity\Http\Controllers\AuthController;
use App\Modules\Identity\Http\Controllers\PersonAccountInvitationAcceptanceController;
use App\Modules\Identity\Http\Controllers\PersonAccountInvitationController;
use App\Modules\Identity\Http\Controllers\PersonProfileController;
use App\Modules\Ministries\Http\Controllers\MinistryTypeController;
use App\Modules\Ministries\Http\Controllers\PersonFunctionController;
use App\Modules\Ministries\Http\Controllers\ServiceFunctionController;
use App\Modules\Missions\Http\Controllers\InternalMissionController;
use App\Modules\Organizations\Http\Controllers\OrganizationController;
use App\Modules\Organizations\Http\Controllers\OrganizationMemberController;
use App\Modules\Organizations\Http\Controllers\OrganizationRelationshipController;
use App\Modules\Scheduling\Http\Controllers\AssignmentController;
use App\Modules\Scheduling\Http\Controllers\EventController;
use App\Modules\Scheduling\Http\Controllers\EventTypeController;
use App\Modules\Scheduling\Http\Controllers\LocationController;
use App\Modules\Scheduling\Http\Controllers\MyAssignmentController;
use App\Modules\Scheduling\Http\Controllers\UnavailabilityController;
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
        Route::get('/me/unavailabilities', [UnavailabilityController::class, 'ownIndex']);
        Route::post('/me/unavailabilities', [UnavailabilityController::class, 'store']);
        Route::delete('/me/unavailabilities/{unavailability}', [UnavailabilityController::class, 'destroy']);
        Route::get('/me/assignments', MyAssignmentController::class)->name('api.v1.me.assignments.index');

        Route::get('/organizations', [OrganizationController::class, 'index'])->name('api.v1.organizations.index');
        Route::post('/organizations', [OrganizationController::class, 'store'])->name('api.v1.organizations.store');
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('api.v1.organizations.show');
        Route::get('/organizations/{organization}/members', [OrganizationMemberController::class, 'index'])
            ->name('api.v1.organizations.members.index');
        Route::post('/organizations/{organization}/members', [OrganizationMemberController::class, 'store'])
            ->name('api.v1.organizations.members.store');
        Route::get('/organizations/{organization}/members/{person}/unavailabilities', [UnavailabilityController::class, 'memberIndex']);
        Route::get('/organizations/{organization}/ministry-types', [MinistryTypeController::class, 'index'])
            ->name('api.v1.organizations.ministry-types.index');
        Route::post('/organizations/{organization}/ministry-types', [MinistryTypeController::class, 'store'])
            ->name('api.v1.organizations.ministry-types.store');
        Route::get('/organizations/{organization}/service-functions', [ServiceFunctionController::class, 'index'])
            ->name('api.v1.organizations.service-functions.index');
        Route::post('/organizations/{organization}/service-functions', [ServiceFunctionController::class, 'store'])
            ->name('api.v1.organizations.service-functions.store');
        Route::get('/organizations/{organization}/event-types', [EventTypeController::class, 'index'])
            ->name('api.v1.organizations.event-types.index');
        Route::post('/organizations/{organization}/event-types', [EventTypeController::class, 'store'])
            ->name('api.v1.organizations.event-types.store');
        Route::get('/organizations/{organization}/locations', [LocationController::class, 'index'])
            ->name('api.v1.organizations.locations.index');
        Route::post('/organizations/{organization}/locations', [LocationController::class, 'store'])
            ->name('api.v1.organizations.locations.store');
        Route::get('/organizations/{organization}/events', [EventController::class, 'index'])
            ->name('api.v1.organizations.events.index');
        Route::post('/organizations/{organization}/events', [EventController::class, 'store'])
            ->name('api.v1.organizations.events.store');
        Route::get('/organizations/{organization}/events/{event}', [EventController::class, 'show'])
            ->name('api.v1.organizations.events.show');
        Route::post('/organizations/{organization}/events/{event}/publish', [EventController::class, 'publish'])
            ->name('api.v1.organizations.events.publish');
        Route::get(
            '/organizations/{organization}/events/{event}/missions',
            [InternalMissionController::class, 'index'],
        )->name('api.v1.organizations.events.missions.index');
        Route::post(
            '/organizations/{organization}/events/{event}/missions',
            [InternalMissionController::class, 'store'],
        )->name('api.v1.organizations.events.missions.store');
        Route::get('/organizations/{organization}/events/{event}/missions/{mission}/assignments', [AssignmentController::class, 'index'])
            ->name('api.v1.organizations.events.missions.assignments.index');
        Route::post('/organizations/{organization}/events/{event}/missions/{mission}/assignments', [AssignmentController::class, 'store'])
            ->name('api.v1.organizations.events.missions.assignments.store');
        Route::get('/organizations/{organization}/events/{event}/missions/{mission}/slots/{slot}/eligible-members', [AssignmentController::class, 'eligibleMembers'])
            ->name('api.v1.organizations.events.missions.slots.eligible-members.index');
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

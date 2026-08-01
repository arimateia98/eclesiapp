<?php

namespace App\Modules\Missions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Missions\Application\Actions\CreateInternalMission;
use App\Modules\Missions\Application\DTOs\CreateInternalMissionData;
use App\Modules\Missions\Application\Queries\ListInternalMissions;
use App\Modules\Missions\Http\Requests\ListInternalMissionsRequest;
use App\Modules\Missions\Http\Requests\StoreInternalMissionRequest;
use App\Modules\Missions\Http\Resources\MissionResource;
use App\Modules\Organizations\Domain\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class InternalMissionController extends Controller
{
    public function index(
        ListInternalMissionsRequest $request,
        Organization $organization,
        string $event,
        ListInternalMissions $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $organization);

        return MissionResource::collection(
            $query->execute($organization, $event, (int) $request->validated('per_page', 100)),
        );
    }

    public function store(
        StoreInternalMissionRequest $request,
        Organization $organization,
        string $event,
        CreateInternalMission $action,
    ): JsonResponse {
        Gate::authorize('manageMembers', $organization);

        /** @var User $user */
        $user = $request->user();
        $mission = $action->execute(
            $user,
            $organization,
            $event,
            CreateInternalMissionData::fromArray($request->validated()),
        );

        return (new MissionResource($mission))->response()->setStatusCode(201);
    }
}

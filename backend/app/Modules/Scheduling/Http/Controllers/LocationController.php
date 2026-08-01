<?php

namespace App\Modules\Scheduling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Scheduling\Application\Actions\CreateLocation;
use App\Modules\Scheduling\Application\DTOs\CreateLocationData;
use App\Modules\Scheduling\Application\Queries\ListLocations;
use App\Modules\Scheduling\Http\Requests\ListSchedulingResourcesRequest;
use App\Modules\Scheduling\Http\Requests\StoreLocationRequest;
use App\Modules\Scheduling\Http\Resources\LocationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class LocationController extends Controller
{
    public function index(
        ListSchedulingResourcesRequest $request,
        Organization $organization,
        ListLocations $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $organization);

        return LocationResource::collection(
            $query->execute($organization, (int) $request->validated('per_page', 100)),
        );
    }

    public function store(
        StoreLocationRequest $request,
        Organization $organization,
        CreateLocation $action,
    ): JsonResponse {
        Gate::authorize('update', $organization);

        /** @var User $user */
        $user = $request->user();
        $location = $action->execute(
            $user,
            $organization,
            CreateLocationData::fromArray($request->validated()),
        );

        return (new LocationResource($location))->response()->setStatusCode(201);
    }
}

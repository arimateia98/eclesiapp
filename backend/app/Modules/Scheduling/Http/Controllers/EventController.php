<?php

namespace App\Modules\Scheduling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Scheduling\Application\Actions\CreateEvent;
use App\Modules\Scheduling\Application\DTOs\CreateEventData;
use App\Modules\Scheduling\Application\Queries\ListEvents;
use App\Modules\Scheduling\Http\Requests\ListSchedulingResourcesRequest;
use App\Modules\Scheduling\Http\Requests\StoreEventRequest;
use App\Modules\Scheduling\Http\Resources\EventResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class EventController extends Controller
{
    public function index(
        ListSchedulingResourcesRequest $request,
        Organization $organization,
        ListEvents $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $organization);

        return EventResource::collection(
            $query->execute($organization, (int) $request->validated('per_page', 100)),
        );
    }

    public function show(
        Organization $organization,
        string $event,
        ListEvents $query,
    ): EventResource {
        Gate::authorize('view', $organization);

        return new EventResource($query->find($organization, $event));
    }

    public function store(
        StoreEventRequest $request,
        Organization $organization,
        CreateEvent $action,
    ): JsonResponse {
        Gate::authorize('manageMembers', $organization);

        /** @var User $user */
        $user = $request->user();
        $event = $action->execute(
            $user,
            $organization,
            CreateEventData::fromArray($request->validated()),
        );

        return (new EventResource($event))->response()->setStatusCode(201);
    }
}

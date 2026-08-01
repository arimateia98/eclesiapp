<?php

namespace App\Modules\Scheduling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Scheduling\Application\Actions\CreateEventType;
use App\Modules\Scheduling\Application\DTOs\CreateEventTypeData;
use App\Modules\Scheduling\Application\Queries\ListEventTypes;
use App\Modules\Scheduling\Http\Requests\ListSchedulingResourcesRequest;
use App\Modules\Scheduling\Http\Requests\StoreEventTypeRequest;
use App\Modules\Scheduling\Http\Resources\EventTypeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class EventTypeController extends Controller
{
    public function index(
        ListSchedulingResourcesRequest $request,
        Organization $organization,
        ListEventTypes $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $organization);

        return EventTypeResource::collection(
            $query->execute($organization, (int) $request->validated('per_page', 100)),
        );
    }

    public function store(
        StoreEventTypeRequest $request,
        Organization $organization,
        CreateEventType $action,
    ): JsonResponse {
        Gate::authorize('update', $organization);

        /** @var User $user */
        $user = $request->user();
        $eventType = $action->execute(
            $user,
            $organization,
            CreateEventTypeData::fromArray($request->validated()),
        );

        return (new EventTypeResource($eventType))->response()->setStatusCode(201);
    }
}

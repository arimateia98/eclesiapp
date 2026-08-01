<?php

namespace App\Modules\Scheduling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Scheduling\Application\Actions\CreateAssignment;
use App\Modules\Scheduling\Application\DTOs\CreateAssignmentData;
use App\Modules\Scheduling\Application\Queries\ListAssignments;
use App\Modules\Scheduling\Http\Requests\ListSchedulingResourcesRequest;
use App\Modules\Scheduling\Http\Requests\StoreAssignmentRequest;
use App\Modules\Scheduling\Http\Resources\AssignmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class AssignmentController extends Controller
{
    public function index(ListSchedulingResourcesRequest $request, Organization $organization, string $event, string $mission, ListAssignments $query): AnonymousResourceCollection
    {
        Gate::authorize('view', $organization);

        return AssignmentResource::collection($query->execute(
            $organization,
            $event,
            $mission,
            (int) $request->validated('per_page', 100),
        ));
    }

    public function store(StoreAssignmentRequest $request, Organization $organization, string $event, string $mission, CreateAssignment $action): JsonResponse
    {
        Gate::authorize('manageMembers', $organization);
        /** @var User $user */ $user = $request->user();
        $assignment = $action->execute($user, $organization, $event, $mission, CreateAssignmentData::fromArray($request->validated()));

        return (new AssignmentResource($assignment))->response()->setStatusCode(201);
    }
}

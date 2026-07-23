<?php

namespace App\Modules\Ministries\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Ministries\Application\Actions\AssignPersonFunction;
use App\Modules\Ministries\Application\Actions\RemovePersonFunction;
use App\Modules\Ministries\Application\DTOs\AssignPersonFunctionData;
use App\Modules\Ministries\Application\Queries\ListPersonFunctions;
use App\Modules\Ministries\Http\Requests\AssignPersonFunctionRequest;
use App\Modules\Ministries\Http\Requests\ListPersonFunctionsRequest;
use App\Modules\Ministries\Http\Resources\PersonFunctionResource;
use App\Modules\Organizations\Domain\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class PersonFunctionController extends Controller
{
    public function index(
        ListPersonFunctionsRequest $request,
        Organization $organization,
        string $person,
        ListPersonFunctions $query,
    ): AnonymousResourceCollection {
        Gate::authorize('manageMembers', $organization);

        return PersonFunctionResource::collection(
            $query->execute($organization, $person, (int) $request->validated('per_page', 100)),
        );
    }

    public function store(
        AssignPersonFunctionRequest $request,
        Organization $organization,
        string $person,
        AssignPersonFunction $action,
    ): JsonResponse {
        Gate::authorize('manageMembers', $organization);

        /** @var User $user */
        $user = $request->user();
        $assignment = $action->execute(
            $user,
            $organization,
            $person,
            AssignPersonFunctionData::fromArray($request->validated()),
        );

        return (new PersonFunctionResource($assignment))->response()->setStatusCode(201);
    }

    public function destroy(
        Organization $organization,
        string $person,
        string $serviceFunction,
        RemovePersonFunction $action,
    ): Response {
        Gate::authorize('manageMembers', $organization);

        /** @var User $user */
        $user = request()->user();
        $action->execute($user, $organization, $person, $serviceFunction);

        return response()->noContent();
    }
}

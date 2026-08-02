<?php

namespace App\Modules\Ministries\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Ministries\Application\Actions\CreateServiceFunction;
use App\Modules\Ministries\Application\DTOs\CreateServiceFunctionData;
use App\Modules\Ministries\Application\Queries\ListServiceFunctions;
use App\Modules\Ministries\Http\Requests\ListServiceFunctionsRequest;
use App\Modules\Ministries\Http\Requests\StoreServiceFunctionRequest;
use App\Modules\Ministries\Http\Resources\ServiceFunctionResource;
use App\Modules\Organizations\Domain\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ServiceFunctionController extends Controller
{
    public function index(
        ListServiceFunctionsRequest $request,
        Organization $organization,
        ListServiceFunctions $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $organization);

        $ministryTypeId = $request->validated('ministry_type_id');

        return ServiceFunctionResource::collection($query->execute(
            $organization,
            (int) $request->validated('per_page', 100),
            is_string($ministryTypeId) ? $ministryTypeId : null,
        ));
    }

    public function store(
        StoreServiceFunctionRequest $request,
        Organization $organization,
        CreateServiceFunction $action,
    ): JsonResponse {
        Gate::authorize('update', $organization);

        /** @var User $user */
        $user = $request->user();
        $serviceFunction = $action->execute(
            $user,
            $organization,
            CreateServiceFunctionData::fromArray($request->validated()),
        );

        return (new ServiceFunctionResource($serviceFunction))->response()->setStatusCode(201);
    }
}

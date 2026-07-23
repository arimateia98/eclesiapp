<?php

namespace App\Modules\Ministries\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Ministries\Application\Actions\CreateMinistryType;
use App\Modules\Ministries\Application\DTOs\CreateMinistryTypeData;
use App\Modules\Ministries\Application\Queries\ListMinistryTypes;
use App\Modules\Ministries\Http\Requests\ListMinistryTypesRequest;
use App\Modules\Ministries\Http\Requests\StoreMinistryTypeRequest;
use App\Modules\Ministries\Http\Resources\MinistryTypeResource;
use App\Modules\Organizations\Domain\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class MinistryTypeController extends Controller
{
    public function index(
        ListMinistryTypesRequest $request,
        Organization $organization,
        ListMinistryTypes $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $organization);

        return MinistryTypeResource::collection(
            $query->execute($organization, (int) $request->validated('per_page', 50)),
        );
    }

    public function store(
        StoreMinistryTypeRequest $request,
        Organization $organization,
        CreateMinistryType $action,
    ): JsonResponse {
        Gate::authorize('update', $organization);

        /** @var User $user */
        $user = $request->user();
        $ministryType = $action->execute(
            $user,
            $organization,
            CreateMinistryTypeData::fromArray($request->validated()),
        );

        return (new MinistryTypeResource($ministryType))->response()->setStatusCode(201);
    }
}

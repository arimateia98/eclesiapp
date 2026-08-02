<?php

namespace App\Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Application\Actions\CreateOrganizationRelationship;
use App\Modules\Organizations\Application\DTOs\CreateOrganizationRelationshipData;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Http\Requests\StoreOrganizationRelationshipRequest;
use App\Modules\Organizations\Http\Resources\OrganizationRelationshipResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class OrganizationRelationshipController extends Controller
{
    public function store(
        StoreOrganizationRelationshipRequest $request,
        Organization $organization,
        CreateOrganizationRelationship $action,
    ): JsonResponse {
        Gate::authorize('createRelationship', $organization);

        /** @var User $user */
        $user = $request->user();
        $relationship = $action->execute(
            $user,
            $organization,
            CreateOrganizationRelationshipData::fromArray($request->validated()),
        );

        return (new OrganizationRelationshipResource($relationship))->response()->setStatusCode(201);
    }
}

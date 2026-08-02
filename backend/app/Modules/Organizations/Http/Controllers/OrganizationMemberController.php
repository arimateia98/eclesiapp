<?php

namespace App\Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Application\Actions\AddOrganizationMember;
use App\Modules\Organizations\Application\DTOs\AddOrganizationMemberData;
use App\Modules\Organizations\Application\Queries\ListOrganizationMembers;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Http\Requests\ListOrganizationMembersRequest;
use App\Modules\Organizations\Http\Requests\StoreOrganizationMemberRequest;
use App\Modules\Organizations\Http\Resources\OrganizationMembershipResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class OrganizationMemberController extends Controller
{
    public function index(
        ListOrganizationMembersRequest $request,
        Organization $organization,
        ListOrganizationMembers $query,
    ): AnonymousResourceCollection {
        Gate::authorize('manageMembers', $organization);

        $perPage = (int) $request->validated('per_page', 20);

        return OrganizationMembershipResource::collection($query->execute($organization, $perPage));
    }

    public function store(
        StoreOrganizationMemberRequest $request,
        Organization $organization,
        AddOrganizationMember $action,
    ): JsonResponse {
        Gate::authorize('manageMembers', $organization);

        /** @var User $user */
        $user = $request->user();
        $membership = $action->execute(
            $user,
            $organization,
            AddOrganizationMemberData::fromArray($request->validated()),
        );

        return (new OrganizationMembershipResource($membership->load('person')))
            ->response()
            ->setStatusCode(201);
    }
}

<?php

namespace App\Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Application\Actions\AddOrganizationMember;
use App\Modules\Organizations\Application\DTOs\AddOrganizationMemberData;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Http\Requests\StoreOrganizationMemberRequest;
use App\Modules\Organizations\Http\Resources\OrganizationMembershipResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class OrganizationMemberController extends Controller
{
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

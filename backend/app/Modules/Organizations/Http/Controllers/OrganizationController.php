<?php

namespace App\Modules\Organizations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Application\Actions\CreateOrganization;
use App\Modules\Organizations\Application\DTOs\CreateOrganizationData;
use App\Modules\Organizations\Application\Queries\ListAccessibleOrganizations;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Http\Requests\ListOrganizationsRequest;
use App\Modules\Organizations\Http\Requests\StoreOrganizationRequest;
use App\Modules\Organizations\Http\Resources\OrganizationResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class OrganizationController extends Controller
{
    public function index(
        ListOrganizationsRequest $request,
        ListAccessibleOrganizations $query,
    ): AnonymousResourceCollection {
        /** @var User $user */
        $user = $request->user();
        $perPage = (int) $request->validated('per_page', 20);

        return OrganizationResource::collection($query->execute($user, $perPage));
    }

    public function store(
        StoreOrganizationRequest $request,
        CreateOrganization $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $organization = $action->execute($user, CreateOrganizationData::fromArray($request->validated()));

        return (new OrganizationResource($organization))->response()->setStatusCode(201);
    }

    public function show(Request $request, Organization $organization): OrganizationResource
    {
        Gate::authorize('view', $organization);

        /** @var User $user */
        $user = $request->user();
        $organization->load(['memberships' => function ($memberships) use ($user): void {
            $memberships
                ->where('status', MembershipStatus::Active)
                ->whereHas('person', fn (Builder $people) => $people->where('user_id', $user->getKey()));
        }]);

        return new OrganizationResource($organization);
    }
}

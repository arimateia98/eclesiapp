<?php

namespace App\Modules\Scheduling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Models\OrganizationMembership;
use App\Modules\Scheduling\Application\Actions\CreateOwnUnavailability;
use App\Modules\Scheduling\Application\DTOs\CreateUnavailabilityData;
use App\Modules\Scheduling\Domain\Models\Unavailability;
use App\Modules\Scheduling\Http\Requests\StoreUnavailabilityRequest;
use App\Modules\Scheduling\Http\Resources\UnavailabilityResource;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class UnavailabilityController extends Controller
{
    public function ownIndex(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */ $user = $request->user();
        $personId = Person::query()->where('user_id', $user->getKey())->value('id');

        return UnavailabilityResource::collection(Unavailability::query()->where('person_id', $personId)->orderBy('starts_at')->get());
    }

    public function store(StoreUnavailabilityRequest $request, CreateOwnUnavailability $action): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        $record = $action->execute($user, CreateUnavailabilityData::fromArray($request->validated()));

        return (new UnavailabilityResource($record))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Unavailability $unavailability): JsonResponse
    {
        /** @var User $user */ $user = $request->user();
        if (! $unavailability->person()->where('user_id', $user->getKey())->exists()) {
            abort(403);
        }
        $unavailability->delete();

        return response()->json(status: 204);
    }

    public function memberIndex(Organization $organization, Person $person): AnonymousResourceCollection
    {
        Gate::authorize('manageMembers', $organization);
        if (! OrganizationMembership::query()->where('organization_id', $organization->getKey())
            ->where('person_id', $person->getKey())->where('status', MembershipStatus::Active)->exists()) {
            throw new DomainRuleViolation('scheduling.person_not_member', 'A pessoa não pertence à organização informada.');
        }

        return UnavailabilityResource::collection(Unavailability::query()->where('person_id', $person->getKey())->orderBy('starts_at')->get());
    }
}

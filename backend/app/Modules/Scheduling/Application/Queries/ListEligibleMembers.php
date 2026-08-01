<?php

namespace App\Modules\Scheduling\Application\Queries;

use App\Modules\Identity\Domain\Enums\PersonStatus;
use App\Modules\Missions\Domain\Models\MissionSlot;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Models\OrganizationMembership;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;

final class ListEligibleMembers
{
    /** @return LengthAwarePaginator<int, OrganizationMembership> */
    public function execute(
        Organization $organization,
        string $eventId,
        string $missionId,
        string $slotId,
        int $perPage,
    ): LengthAwarePaginator {
        $slot = MissionSlot::query()
            ->whereKey($slotId)
            ->where('organization_id', $organization->getKey())
            ->where('mission_id', $missionId)
            ->whereHas('mission', fn ($query) => $query->where('event_id', $eventId))
            ->first();

        if ($slot === null || $slot->service_function_id === null) {
            throw new DomainRuleViolation(
                'scheduling.mission_slot_unavailable',
                'A vaga não pertence à missão informada.',
            );
        }

        return OrganizationMembership::query()
            ->with('person')
            ->where('organization_id', $organization->getKey())
            ->where('status', MembershipStatus::Active)
            ->whereHas('person', fn ($query) => $query->where('status', PersonStatus::Active))
            ->whereExists(fn (Builder $query) => $query
                ->selectRaw('1')
                ->from('person_functions')
                ->whereColumn('person_functions.person_id', 'organization_memberships.person_id')
                ->where('person_functions.organization_id', $organization->getKey())
                ->where('person_functions.service_function_id', $slot->service_function_id))
            ->orderBy('joined_at')
            ->paginate($perPage);
    }
}

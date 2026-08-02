<?php

namespace App\Modules\Scheduling\Application\Queries;

use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Scheduling\Domain\Models\Assignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListAssignments
{
    /** @return LengthAwarePaginator<int, Assignment> */
    public function execute(Organization $organization, string $eventId, string $missionId, int $perPage): LengthAwarePaginator
    {
        return Assignment::query()->with(['person', 'missionSlot.serviceFunction'])
            ->where('organization_id', $organization->getKey())->where('mission_id', $missionId)
            ->whereHas('mission', fn ($query) => $query->where('event_id', $eventId))
            ->orderBy('assigned_at')->paginate($perPage);
    }
}

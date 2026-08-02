<?php

namespace App\Modules\Missions\Application\Queries;

use App\Modules\Missions\Domain\Models\Mission;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Scheduling\Domain\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListInternalMissions
{
    /** @return LengthAwarePaginator<int, Mission> */
    public function execute(
        Organization $organization,
        string $eventId,
        int $perPage,
    ): LengthAwarePaginator {
        Event::query()
            ->whereKey($eventId)
            ->where('publisher_organization_id', $organization->getKey())
            ->firstOrFail();

        return Mission::query()
            ->with(['event', 'ministryType', 'slots.serviceFunction'])
            ->where('publisher_organization_id', $organization->getKey())
            ->where('event_id', $eventId)
            ->orderBy('created_at')
            ->paginate($perPage);
    }
}

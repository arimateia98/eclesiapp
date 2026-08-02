<?php

namespace App\Modules\Scheduling\Application\Queries;

use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Scheduling\Domain\Enums\EventStatus;
use App\Modules\Scheduling\Domain\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListEvents
{
    /** @return LengthAwarePaginator<int, Event> */
    public function execute(Organization $organization, int $perPage): LengthAwarePaginator
    {
        return Event::query()
            ->with(['eventType', 'location'])
            ->where('publisher_organization_id', $organization->getKey())
            ->where('status', '!=', EventStatus::Cancelled)
            ->orderBy('starts_at')
            ->paginate($perPage);
    }

    public function find(Organization $organization, string $eventId): Event
    {
        return Event::query()
            ->with(['eventType', 'location', 'missions.ministryType', 'missions.slots.serviceFunction'])
            ->whereKey($eventId)
            ->where('publisher_organization_id', $organization->getKey())
            ->firstOrFail();
    }
}

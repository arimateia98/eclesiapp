<?php

namespace App\Modules\Scheduling\Application\Queries;

use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Scheduling\Domain\Models\EventType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListEventTypes
{
    /** @return LengthAwarePaginator<int, EventType> */
    public function execute(Organization $organization, int $perPage): LengthAwarePaginator
    {
        return EventType::query()
            ->where('organization_id', $organization->getKey())
            ->where('active', true)
            ->orderBy('name')
            ->paginate($perPage);
    }
}

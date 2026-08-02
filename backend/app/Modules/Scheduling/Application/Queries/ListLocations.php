<?php

namespace App\Modules\Scheduling\Application\Queries;

use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Scheduling\Domain\Models\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListLocations
{
    /** @return LengthAwarePaginator<int, Location> */
    public function execute(Organization $organization, int $perPage): LengthAwarePaginator
    {
        return Location::query()
            ->where('organization_id', $organization->getKey())
            ->where('active', true)
            ->orderBy('name')
            ->paginate($perPage);
    }
}

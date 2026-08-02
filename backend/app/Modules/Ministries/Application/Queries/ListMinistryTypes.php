<?php

namespace App\Modules\Ministries\Application\Queries;

use App\Modules\Ministries\Domain\Models\MinistryType;
use App\Modules\Organizations\Domain\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListMinistryTypes
{
    /** @return LengthAwarePaginator<int, MinistryType> */
    public function execute(Organization $organization, int $perPage): LengthAwarePaginator
    {
        return MinistryType::query()
            ->where('organization_id', $organization->getKey())
            ->where('active', true)
            ->orderBy('name')
            ->paginate($perPage);
    }
}

<?php

namespace App\Modules\Ministries\Application\Queries;

use App\Modules\Ministries\Domain\Models\ServiceFunction;
use App\Modules\Organizations\Domain\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListServiceFunctions
{
    /** @return LengthAwarePaginator<int, ServiceFunction> */
    public function execute(
        Organization $organization,
        int $perPage,
        ?string $ministryTypeId,
    ): LengthAwarePaginator {
        return ServiceFunction::query()
            ->with('ministryType')
            ->where('organization_id', $organization->getKey())
            ->where('active', true)
            ->whereHas('ministryType', fn ($query) => $query->where('active', true))
            ->when(
                $ministryTypeId !== null,
                fn ($query) => $query->where('ministry_type_id', $ministryTypeId),
            )
            ->orderBy('name')
            ->paginate($perPage);
    }
}

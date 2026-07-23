<?php

namespace App\Modules\Organizations\Application\Queries;

use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Models\OrganizationMembership;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListOrganizationMembers
{
    /** @return LengthAwarePaginator<int, OrganizationMembership> */
    public function execute(Organization $organization, int $perPage): LengthAwarePaginator
    {
        return OrganizationMembership::query()
            ->with('person')
            ->where('organization_id', $organization->getKey())
            ->where('status', MembershipStatus::Active)
            ->orderByDesc('joined_at')
            ->paginate($perPage);
    }
}

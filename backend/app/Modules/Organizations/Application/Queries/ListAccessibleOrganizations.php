<?php

namespace App\Modules\Organizations\Application\Queries;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Enums\OrganizationVisibility;
use App\Modules\Organizations\Domain\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ListAccessibleOrganizations
{
    /** @return LengthAwarePaginator<int, Organization> */
    public function execute(User $user, int $perPage): LengthAwarePaginator
    {
        return Organization::query()
            ->with(['memberships' => function ($memberships) use ($user): void {
                $memberships
                    ->where('status', MembershipStatus::Active)
                    ->whereHas('person', fn (Builder $people) => $people->where('user_id', $user->getKey()));
            }])
            ->where(function (Builder $query) use ($user): void {
                $query
                    ->where('visibility', OrganizationVisibility::Public)
                    ->orWhereHas('memberships', function (Builder $memberships) use ($user): void {
                        $memberships
                            ->where('status', MembershipStatus::Active)
                            ->whereHas('person', fn (Builder $people) => $people->where('user_id', $user->getKey()));
                    });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }
}

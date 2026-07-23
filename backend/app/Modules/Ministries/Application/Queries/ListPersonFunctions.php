<?php

namespace App\Modules\Ministries\Application\Queries;

use App\Modules\Ministries\Domain\Models\PersonFunction;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListPersonFunctions
{
    /** @return LengthAwarePaginator<int, PersonFunction> */
    public function execute(
        Organization $organization,
        string $personId,
        int $perPage,
    ): LengthAwarePaginator {
        $isActiveMember = $organization->memberships()
            ->where('person_id', $personId)
            ->where('status', MembershipStatus::Active)
            ->exists();

        if (! $isActiveMember) {
            throw new DomainRuleViolation(
                errorCode: 'ministries.person_not_active_member',
                message: 'A pessoa precisa ser membro ativo desta organização.',
            );
        }

        return PersonFunction::query()
            ->with('serviceFunction.ministryType')
            ->where('organization_id', $organization->getKey())
            ->where('person_id', $personId)
            ->whereHas('serviceFunction', fn ($query) => $query->where('active', true))
            ->orderBy('created_at')
            ->paginate($perPage);
    }
}

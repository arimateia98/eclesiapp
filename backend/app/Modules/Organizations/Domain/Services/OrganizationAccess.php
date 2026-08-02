<?php

namespace App\Modules\Organizations\Domain\Services;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Enums\MembershipRole;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Enums\OrganizationVisibility;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Models\OrganizationMembership;

final class OrganizationAccess
{
    public function canView(User $user, Organization $organization): bool
    {
        return $organization->visibility === OrganizationVisibility::Public
            || $this->membership($user, $organization) !== null;
    }

    public function canManage(User $user, Organization $organization): bool
    {
        $membership = $this->membership($user, $organization);

        return $membership !== null && in_array($membership->role, [
            MembershipRole::Owner,
            MembershipRole::Administrator,
        ], true);
    }

    public function canManageMembers(User $user, Organization $organization): bool
    {
        $membership = $this->membership($user, $organization);

        return $membership !== null && in_array($membership->role, [
            MembershipRole::Owner,
            MembershipRole::Administrator,
            MembershipRole::Coordinator,
        ], true);
    }

    public function canAssignRole(User $user, Organization $organization, MembershipRole $role): bool
    {
        $membership = $this->membership($user, $organization);

        if ($membership === null) {
            return false;
        }

        return match ($membership->role) {
            MembershipRole::Owner => true,
            MembershipRole::Administrator => in_array($role, [
                MembershipRole::Coordinator,
                MembershipRole::Member,
                MembershipRole::Guest,
            ], true),
            MembershipRole::Coordinator => in_array($role, [
                MembershipRole::Member,
                MembershipRole::Guest,
            ], true),
            default => false,
        };
    }

    public function membership(User $user, Organization $organization): ?OrganizationMembership
    {
        return OrganizationMembership::query()
            ->where('organization_id', $organization->getKey())
            ->where('status', MembershipStatus::Active)
            ->whereHas('person', fn ($query) => $query->where('user_id', $user->getKey()))
            ->first();
    }
}

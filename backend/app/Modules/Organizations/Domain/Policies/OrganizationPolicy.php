<?php

namespace App\Modules\Organizations\Domain\Policies;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;

final readonly class OrganizationPolicy
{
    public function __construct(private OrganizationAccess $access) {}

    public function view(User $user, Organization $organization): bool
    {
        return $this->access->canView($user, $organization);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->access->canManage($user, $organization);
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $this->access->canManageMembers($user, $organization);
    }

    public function createRelationship(User $user, Organization $organization): bool
    {
        return $this->access->canManage($user, $organization);
    }
}

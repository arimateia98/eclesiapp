<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Policies;

use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\User;
use App\Modules\PastoralOrganization\Support\ParishPastoralAccess;

final class PastoralAreaPolicy
{
    public function __construct(private readonly ParishPastoralAccess $access) {}

    public function viewAny(User $user, Parish $parish): bool
    {
        return $this->access->canManage($user, $parish);
    }

    public function create(User $user, Parish $parish): bool
    {
        return $this->access->canManage($user, $parish);
    }
}

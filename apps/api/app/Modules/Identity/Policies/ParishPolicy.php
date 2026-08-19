<?php

declare(strict_types=1);

namespace App\Modules\Identity\Policies;

use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\User;

final class ParishPolicy
{
    public function view(User $user, Parish $parish): bool
    {
        if ($parish->status !== 'ACTIVE') {
            return false;
        }

        return $user->memberships()
            ->where('parish_id', $parish->id)
            ->where('status', 'ACTIVE')
            ->exists();
    }
}

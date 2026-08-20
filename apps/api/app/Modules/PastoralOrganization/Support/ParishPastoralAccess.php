<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Support;

use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\ParishUserRole;
use App\Modules\Identity\Models\User;

final class ParishPastoralAccess
{
    public function canManage(User $user, Parish $parish): bool
    {
        if ($parish->status !== 'ACTIVE' || ! $user->memberships()
            ->where('parish_id', $parish->id)
            ->where('status', 'ACTIVE')
            ->exists()) {
            return false;
        }

        $today = now()->toDateString();

        return ParishUserRole::query()
            ->where('parish_id', $parish->id)
            ->where('user_id', $user->id)
            ->whereDate('starts_on', '<=', $today)
            ->where(function ($query) use ($today): void {
                $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today);
            })
            ->whereHas('role', fn ($query) => $query->whereIn('code', ['PARISH_PRIEST', 'PARISH_ADMIN']))
            ->exists();
    }
}

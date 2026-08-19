<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Policies;

use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\ParishUserRole;
use App\Modules\Identity\Models\User;
use App\Modules\PastoralOrganization\Models\Servant;

final class ServantPolicy
{
    public function viewAny(User $user, Parish $parish): bool
    {
        return $this->canManage($user, $parish);
    }

    public function create(User $user, Parish $parish): bool
    {
        return $this->canManage($user, $parish);
    }

    public function update(User $user, Servant $servant): bool
    {
        $parish = $servant->parish;

        return $parish instanceof Parish && $this->canManage($user, $parish);
    }

    private function canManage(User $user, Parish $parish): bool
    {
        $hasActiveMembership = $user->memberships()
            ->where('parish_id', $parish->id)
            ->where('status', 'ACTIVE')
            ->exists();

        if (! $hasActiveMembership || $parish->status !== 'ACTIVE') {
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

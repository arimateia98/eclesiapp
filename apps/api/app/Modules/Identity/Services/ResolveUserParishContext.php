<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Exceptions\ParishContextException;
use App\Modules\Identity\Models\ParishUserMembership;
use App\Modules\Identity\Models\ParishUserRole;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\ActiveParishContext;
use Illuminate\Database\Eloquent\Collection;

final class ResolveUserParishContext
{
    public function resolve(User $user, ?string $requestedParishId): ActiveParishContext
    {
        $memberships = $this->activeMemberships($user);

        if ($requestedParishId === null) {
            if ($memberships->isEmpty()) {
                throw ParishContextException::unavailable();
            }

            if ($memberships->count() !== 1) {
                throw ParishContextException::required();
            }

            $membership = $memberships->first();
        } else {
            $membership = $memberships->firstWhere('parish_id', $requestedParishId);

            if (! $membership) {
                throw ParishContextException::accessDenied();
            }
        }

        $parish = $membership->parish;

        if (! $parish) {
            throw ParishContextException::accessDenied();
        }

        $today = now()->toDateString();
        $roleCodes = ParishUserRole::query()
            ->where('parish_id', $parish->id)
            ->where('user_id', $user->id)
            ->whereDate('starts_on', '<=', $today)
            ->where(function ($query) use ($today): void {
                $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today);
            })
            ->with('role')
            ->get()
            ->map(fn (ParishUserRole $grant): ?string => $grant->role?->code)
            ->filter(fn (?string $code): bool => $code !== null)
            ->values()
            ->all();

        return new ActiveParishContext($parish, $membership, array_values($roleCodes));
    }

    /** @return Collection<int, ParishUserMembership> */
    private function activeMemberships(User $user): Collection
    {
        return $user->memberships()
            ->where('status', 'ACTIVE')
            ->whereHas('parish', fn ($query) => $query->where('status', 'ACTIVE'))
            ->with('parish')
            ->get();
    }
}

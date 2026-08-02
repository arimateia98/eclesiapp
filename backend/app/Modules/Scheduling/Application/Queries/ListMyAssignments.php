<?php

namespace App\Modules\Scheduling\Application\Queries;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Scheduling\Domain\Enums\AssignmentStatus;
use App\Modules\Scheduling\Domain\Enums\EventStatus;
use App\Modules\Scheduling\Domain\Models\Assignment;
use App\Modules\Scheduling\Domain\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListMyAssignments
{
    /** @return LengthAwarePaginator<int, Assignment> */
    public function execute(User $user, int $perPage): LengthAwarePaginator
    {
        return Assignment::query()
            ->with(['organization', 'mission.event.location', 'missionSlot.serviceFunction'])
            ->where('status', AssignmentStatus::Confirmed)
            ->whereHas('person', fn ($query) => $query->where('user_id', $user->getKey()))
            ->whereHas('mission.event', fn ($query) => $query->where('status', EventStatus::Published))
            ->orderBy(
                Event::query()->select('starts_at')->whereColumn('events.id', 'missions.event_id')
                    ->join('missions', 'missions.event_id', '=', 'events.id')
                    ->whereColumn('missions.id', 'assignments.mission_id')
                    ->limit(1),
            )
            ->paginate($perPage);
    }
}

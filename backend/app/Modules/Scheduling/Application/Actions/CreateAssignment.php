<?php

namespace App\Modules\Scheduling\Application\Actions;

use App\Modules\Identity\Domain\Enums\PersonStatus;
use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Ministries\Domain\Models\PersonFunction;
use App\Modules\Missions\Domain\Models\MissionSlot;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Models\OrganizationMembership;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;
use App\Modules\Scheduling\Application\DTOs\CreateAssignmentData;
use App\Modules\Scheduling\Domain\Enums\AssignmentStatus;
use App\Modules\Scheduling\Domain\Models\Assignment;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class CreateAssignment
{
    public function __construct(private OrganizationAccess $access, private AuditRecorder $audit) {}

    public function execute(User $actor, Organization $organization, string $eventId, string $missionId, CreateAssignmentData $data): Assignment
    {
        if (! $this->access->canManageMembers($actor, $organization)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $organization, $eventId, $missionId, $data): Assignment {
            $person = Person::query()->whereKey($data->personId)->where('status', PersonStatus::Active)->lockForUpdate()->first();
            if ($person === null) {
                throw new DomainRuleViolation('scheduling.person_unavailable', 'A pessoa não está disponível para designação.');
            }

            $slot = MissionSlot::query()->with('mission.event')->whereKey($data->missionSlotId)
                ->where('organization_id', $organization->getKey())->where('mission_id', $missionId)->lockForUpdate()->first();
            $mission = $slot?->mission;
            if ($slot === null || $mission === null || (string) $mission->event_id !== $eventId) {
                throw new DomainRuleViolation('scheduling.mission_slot_unavailable', 'A vaga não pertence à missão informada.');
            }

            $isMember = OrganizationMembership::query()->where('organization_id', $organization->getKey())
                ->where('person_id', $person->getKey())->where('status', MembershipStatus::Active)->exists();
            $isQualified = $slot->service_function_id !== null && PersonFunction::query()
                ->where('organization_id', $organization->getKey())->where('person_id', $person->getKey())
                ->where('service_function_id', $slot->service_function_id)->exists();
            if (! $isMember || ! $isQualified) {
                throw new DomainRuleViolation('scheduling.person_not_qualified', 'A pessoa precisa ser membro ativo e possuir a função exigida.');
            }

            $active = [AssignmentStatus::Pending, AssignmentStatus::Confirmed];
            if (Assignment::query()->where('mission_slot_id', $slot->getKey())->whereIn('status', $active)->count() >= $slot->quantity) {
                throw new DomainRuleViolation('scheduling.mission_slot_full', 'A vaga já atingiu sua capacidade.', 409);
            }

            $event = $mission->event;
            if ($event === null) {
                throw new DomainRuleViolation('scheduling.event_unavailable', 'O evento da missão não está disponível.');
            }
            $hasConflict = Assignment::query()->where('person_id', $person->getKey())->whereIn('status', $active)
                ->whereHas('mission.event', fn ($query) => $query
                    ->where('starts_at', '<', $event->ends_at)->where('ends_at', '>', $event->starts_at))->exists();
            if ($hasConflict) {
                throw new DomainRuleViolation('scheduling.assignment_time_conflict', 'A pessoa já possui uma designação em horário sobreposto.', 409);
            }

            $assignment = Assignment::query()->create([
                'organization_id' => $organization->getKey(), 'mission_id' => $missionId,
                'mission_slot_id' => $slot->getKey(), 'person_id' => $person->getKey(),
                'assigned_by' => $actor->getKey(), 'status' => AssignmentStatus::Pending, 'assigned_at' => now(),
            ]);
            $assignment->load(['person', 'missionSlot.serviceFunction']);
            $this->audit->record((string) $actor->getKey(), (string) $organization->getKey(), AuditAction::AssignmentCreated,
                'assignment', (string) $assignment->getKey(), null, [
                    'mission_id' => $missionId, 'mission_slot_id' => (string) $slot->getKey(),
                    'person_id' => (string) $person->getKey(), 'status' => AssignmentStatus::Pending->value,
                ]);

            return $assignment;
        });
    }
}

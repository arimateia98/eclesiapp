<?php

namespace App\Modules\Missions\Application\Actions;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Ministries\Domain\Models\MinistryType;
use App\Modules\Ministries\Domain\Models\ServiceFunction;
use App\Modules\Missions\Application\DTOs\CreateInternalMissionData;
use App\Modules\Missions\Domain\Enums\MissionParticipationPolicy;
use App\Modules\Missions\Domain\Enums\MissionSlotType;
use App\Modules\Missions\Domain\Enums\MissionStatus;
use App\Modules\Missions\Domain\Enums\MissionVisibility;
use App\Modules\Missions\Domain\Models\Mission;
use App\Modules\Missions\Domain\Models\MissionSlot;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;
use App\Modules\Scheduling\Domain\Enums\EventStatus;
use App\Modules\Scheduling\Domain\Models\Event;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class CreateInternalMission
{
    public function __construct(
        private OrganizationAccess $access,
        private AuditRecorder $audit,
    ) {}

    public function execute(
        User $actor,
        Organization $organization,
        string $eventId,
        CreateInternalMissionData $data,
    ): Mission {
        if (! $this->access->canManageMembers($actor, $organization)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $organization, $eventId, $data): Mission {
            $event = Event::query()
                ->whereKey($eventId)
                ->where('publisher_organization_id', $organization->getKey())
                ->whereNotIn('status', [EventStatus::Cancelled, EventStatus::Completed])
                ->lockForUpdate()
                ->first();

            if ($event === null) {
                throw new DomainRuleViolation(
                    errorCode: 'missions.event_unavailable',
                    message: 'O evento não está disponível nesta organização.',
                );
            }

            $ministryType = MinistryType::query()
                ->whereKey($data->ministryTypeId)
                ->where('organization_id', $organization->getKey())
                ->where('active', true)
                ->lockForUpdate()
                ->first();

            if ($ministryType === null) {
                throw new DomainRuleViolation(
                    errorCode: 'missions.ministry_type_unavailable',
                    message: 'O tipo de ministério não está ativo nesta organização.',
                );
            }

            $functionIds = array_map(fn ($slot) => $slot->serviceFunctionId, $data->slots);

            if (count($functionIds) !== count(array_unique($functionIds))) {
                throw new DomainRuleViolation(
                    errorCode: 'missions.duplicate_service_function_slot',
                    message: 'Cada função de serviço pode aparecer apenas uma vez na missão.',
                );
            }

            $serviceFunctions = ServiceFunction::query()
                ->whereIn('id', $functionIds)
                ->where('organization_id', $organization->getKey())
                ->where('ministry_type_id', $ministryType->getKey())
                ->where('active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (ServiceFunction $function) => (string) $function->getKey());

            if ($serviceFunctions->count() !== count($functionIds)) {
                throw new DomainRuleViolation(
                    errorCode: 'missions.service_function_unavailable',
                    message: 'Todas as vagas devem usar funções ativas do ministério selecionado.',
                );
            }

            $mission = Mission::query()->create([
                'event_id' => $event->getKey(),
                'publisher_organization_id' => $organization->getKey(),
                'target_organization_id' => $organization->getKey(),
                'ministry_type_id' => $ministryType->getKey(),
                'title' => trim($data->title),
                'description' => $data->description,
                'visibility' => MissionVisibility::Private,
                'participation_policy' => MissionParticipationPolicy::CoordinatorAssignment,
                'status' => MissionStatus::Draft,
                'response_deadline' => null,
                'created_by' => $actor->getKey(),
            ]);

            foreach ($data->slots as $slotData) {
                MissionSlot::query()->create([
                    'organization_id' => $organization->getKey(),
                    'mission_id' => $mission->getKey(),
                    'slot_type' => MissionSlotType::Person,
                    'service_function_id' => $slotData->serviceFunctionId,
                    'quantity' => $slotData->quantity,
                    'required' => $slotData->required,
                ]);
            }

            $mission->setRelation('event', $event);
            $mission->setRelation('ministryType', $ministryType);
            $mission->load('slots.serviceFunction');

            $this->audit->record(
                actorUserId: (string) $actor->getKey(),
                organizationId: (string) $organization->getKey(),
                action: AuditAction::MissionCreated,
                entityType: 'mission',
                entityId: (string) $mission->getKey(),
                previousState: null,
                newState: [
                    'event_id' => (string) $event->getKey(),
                    'ministry_type_id' => (string) $ministryType->getKey(),
                    'visibility' => MissionVisibility::Private->value,
                    'participation_policy' => MissionParticipationPolicy::CoordinatorAssignment->value,
                    'status' => MissionStatus::Draft->value,
                    'slot_count' => count($data->slots),
                ],
            );

            return $mission;
        });
    }
}

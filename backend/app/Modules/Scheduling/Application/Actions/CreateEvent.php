<?php

namespace App\Modules\Scheduling\Application\Actions;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;
use App\Modules\Scheduling\Application\DTOs\CreateEventData;
use App\Modules\Scheduling\Domain\Enums\EventStatus;
use App\Modules\Scheduling\Domain\Enums\EventVisibility;
use App\Modules\Scheduling\Domain\Models\Event;
use App\Modules\Scheduling\Domain\Models\EventType;
use App\Modules\Scheduling\Domain\Models\Location;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class CreateEvent
{
    public function __construct(
        private OrganizationAccess $access,
        private AuditRecorder $audit,
    ) {}

    public function execute(User $actor, Organization $organization, CreateEventData $data): Event
    {
        if (! $this->access->canManageMembers($actor, $organization)) {
            throw new AuthorizationException;
        }

        if ($data->endsAt->lessThanOrEqualTo($data->startsAt)) {
            throw new DomainRuleViolation(
                errorCode: 'scheduling.event_time_range_invalid',
                message: 'O término do evento deve ocorrer depois do início.',
            );
        }

        return DB::transaction(function () use ($actor, $organization, $data): Event {
            $eventType = EventType::query()
                ->whereKey($data->eventTypeId)
                ->where('organization_id', $organization->getKey())
                ->where('active', true)
                ->lockForUpdate()
                ->first();

            if ($eventType === null) {
                throw new DomainRuleViolation(
                    errorCode: 'scheduling.event_type_unavailable',
                    message: 'O tipo de evento não está ativo nesta organização.',
                );
            }

            $location = null;

            if ($data->locationId !== null) {
                $location = Location::query()
                    ->whereKey($data->locationId)
                    ->where('organization_id', $organization->getKey())
                    ->where('active', true)
                    ->lockForUpdate()
                    ->first();

                if ($location === null) {
                    throw new DomainRuleViolation(
                        errorCode: 'scheduling.location_unavailable',
                        message: 'O local não está ativo nesta organização.',
                    );
                }
            }

            $event = Event::query()->create([
                'publisher_organization_id' => $organization->getKey(),
                'host_organization_id' => $organization->getKey(),
                'event_type_id' => $eventType->getKey(),
                'location_id' => $location?->getKey(),
                'title' => trim($data->title),
                'description' => $data->description,
                'starts_at' => $data->startsAt,
                'ends_at' => $data->endsAt,
                'visibility' => EventVisibility::Private,
                'status' => EventStatus::Draft,
                'created_by' => $actor->getKey(),
            ]);
            $event->setRelation('eventType', $eventType);
            $event->setRelation('location', $location);

            $this->audit->record(
                actorUserId: (string) $actor->getKey(),
                organizationId: (string) $organization->getKey(),
                action: AuditAction::EventCreated,
                entityType: 'event',
                entityId: (string) $event->getKey(),
                previousState: null,
                newState: [
                    'event_type_id' => (string) $eventType->getKey(),
                    'location_id' => $location?->getKey(),
                    'starts_at' => $data->startsAt->toISOString(),
                    'ends_at' => $data->endsAt->toISOString(),
                    'visibility' => EventVisibility::Private->value,
                    'status' => EventStatus::Draft->value,
                ],
            );

            return $event;
        });
    }
}

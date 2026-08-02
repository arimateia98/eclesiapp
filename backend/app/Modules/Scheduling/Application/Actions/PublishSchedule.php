<?php

namespace App\Modules\Scheduling\Application\Actions;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Missions\Domain\Enums\MissionStatus;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;
use App\Modules\Scheduling\Domain\Enums\AssignmentStatus;
use App\Modules\Scheduling\Domain\Enums\EventStatus;
use App\Modules\Scheduling\Domain\Models\Assignment;
use App\Modules\Scheduling\Domain\Models\Event;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class PublishSchedule
{
    public function __construct(private OrganizationAccess $access, private AuditRecorder $audit) {}

    public function execute(User $actor, Organization $organization, string $eventId): Event
    {
        if (! $this->access->canManageMembers($actor, $organization)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $organization, $eventId): Event {
            $event = Event::query()->with(['missions.slots'])->whereKey($eventId)
                ->where('publisher_organization_id', $organization->getKey())->lockForUpdate()->first();
            if ($event === null) {
                throw new DomainRuleViolation('scheduling.event_unavailable', 'O evento não pertence à organização informada.');
            }
            if ($event->status !== EventStatus::Draft) {
                throw new DomainRuleViolation('scheduling.schedule_not_draft', 'Somente uma escala em rascunho pode ser publicada.', 409);
            }
            if ($event->missions->isEmpty()) {
                throw new DomainRuleViolation('scheduling.schedule_without_missions', 'A escala precisa possuir ao menos uma missão.', 409);
            }

            $active = [AssignmentStatus::Pending, AssignmentStatus::Confirmed];
            foreach ($event->missions as $mission) {
                foreach ($mission->slots->where('required', true) as $slot) {
                    $assigned = Assignment::query()->where('mission_slot_id', $slot->getKey())
                        ->whereIn('status', $active)->lockForUpdate()->count();
                    if ($assigned < $slot->quantity) {
                        throw new DomainRuleViolation('scheduling.required_slot_unfilled', 'Todas as vagas obrigatórias devem estar preenchidas.', 409);
                    }
                }
            }

            $publishedAt = now();
            Assignment::query()->whereIn('mission_id', $event->missions->modelKeys())
                ->where('status', AssignmentStatus::Pending)
                ->update(['status' => AssignmentStatus::Confirmed, 'confirmed_at' => $publishedAt, 'updated_at' => $publishedAt]);
            foreach ($event->missions as $mission) {
                $mission->update(['status' => MissionStatus::Filled]);
            }
            $event->update(['status' => EventStatus::Published]);

            $this->audit->record(
                (string) $actor->getKey(), (string) $organization->getKey(), AuditAction::EventPublished,
                'event', (string) $event->getKey(), ['status' => EventStatus::Draft->value],
                ['status' => EventStatus::Published->value, 'published_at' => $publishedAt->toISOString()],
            );

            return $event->refresh()->load(['eventType', 'location', 'missions.slots.serviceFunction']);
        });
    }
}

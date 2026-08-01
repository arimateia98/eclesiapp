<?php

namespace App\Modules\Scheduling\Application\Actions;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;
use App\Modules\Scheduling\Application\DTOs\CreateEventTypeData;
use App\Modules\Scheduling\Domain\Models\EventType;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateEventType
{
    public function __construct(
        private OrganizationAccess $access,
        private AuditRecorder $audit,
    ) {}

    public function execute(User $actor, Organization $organization, CreateEventTypeData $data): EventType
    {
        if (! $this->access->canManage($actor, $organization)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $organization, $data): EventType {
            Organization::query()->whereKey($organization->getKey())->lockForUpdate()->firstOrFail();
            $slug = Str::slug(trim($data->name));

            if ($slug === '') {
                throw new DomainRuleViolation(
                    errorCode: 'scheduling.event_type_name_invalid',
                    message: 'Informe um nome válido para o tipo de evento.',
                );
            }

            if (EventType::query()
                ->where('organization_id', $organization->getKey())
                ->where('slug', $slug)
                ->exists()) {
                throw new DomainRuleViolation(
                    errorCode: 'scheduling.event_type_already_exists',
                    message: 'Já existe um tipo de evento com este nome na organização.',
                    httpStatus: 409,
                );
            }

            $eventType = EventType::query()->create([
                'organization_id' => $organization->getKey(),
                'name' => trim($data->name),
                'slug' => $slug,
                'active' => true,
                'created_by' => $actor->getKey(),
            ]);

            $this->audit->record(
                actorUserId: (string) $actor->getKey(),
                organizationId: (string) $organization->getKey(),
                action: AuditAction::EventTypeCreated,
                entityType: 'event_type',
                entityId: (string) $eventType->getKey(),
                previousState: null,
                newState: ['name' => $eventType->name, 'slug' => $slug, 'active' => true],
            );

            return $eventType;
        });
    }
}

<?php

namespace App\Modules\Organizations\Application\Actions;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Application\DTOs\CreateOrganizationRelationshipData;
use App\Modules\Organizations\Domain\Enums\OrganizationRelationshipStatus;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Models\OrganizationRelationship;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class CreateOrganizationRelationship
{
    public function __construct(
        private OrganizationAccess $access,
        private AuditRecorder $audit,
    ) {}

    public function execute(
        User $actor,
        Organization $source,
        CreateOrganizationRelationshipData $data,
    ): OrganizationRelationship {
        if ((string) $source->getKey() === $data->targetOrganizationId) {
            throw new DomainRuleViolation(
                errorCode: 'organizations.relationship_self_reference',
                message: 'Uma organização não pode se relacionar consigo mesma.',
            );
        }

        $target = Organization::query()->findOrFail($data->targetOrganizationId);

        if (! $this->access->canManage($actor, $source) || ! $this->access->canManage($actor, $target)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $source, $target, $data): OrganizationRelationship {
            $ids = [(string) $source->getKey(), (string) $target->getKey()];
            sort($ids);

            Organization::query()->whereKey($ids)->lockForUpdate()->get();

            $duplicateExists = OrganizationRelationship::query()
                ->where('source_organization_id', $source->getKey())
                ->where('target_organization_id', $target->getKey())
                ->where('relationship_type', $data->type)
                ->where('status', OrganizationRelationshipStatus::Active)
                ->exists();

            if ($duplicateExists) {
                throw new DomainRuleViolation(
                    errorCode: 'organizations.relationship_already_active',
                    message: 'Esta relação entre organizações já está ativa.',
                    httpStatus: 409,
                );
            }

            $relationship = OrganizationRelationship::query()->create([
                'source_organization_id' => $source->getKey(),
                'target_organization_id' => $target->getKey(),
                'relationship_type' => $data->type,
                'status' => OrganizationRelationshipStatus::Active,
                'started_at' => now(),
                'created_by' => $actor->getKey(),
            ]);

            $this->audit->record(
                actorUserId: (string) $actor->getKey(),
                organizationId: (string) $source->getKey(),
                action: AuditAction::RelationshipCreated,
                entityType: 'organization_relationship',
                entityId: (string) $relationship->getKey(),
                previousState: null,
                newState: [
                    'source_organization_id' => (string) $source->getKey(),
                    'target_organization_id' => (string) $target->getKey(),
                    'relationship_type' => $data->type->value,
                    'status' => OrganizationRelationshipStatus::Active->value,
                ],
            );

            return $relationship;
        });
    }
}

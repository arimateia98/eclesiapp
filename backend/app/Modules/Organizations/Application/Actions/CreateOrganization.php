<?php

namespace App\Modules\Organizations\Application\Actions;

use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Application\DTOs\CreateOrganizationData;
use App\Modules\Organizations\Domain\Enums\MembershipRole;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Enums\OrganizationStatus;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Models\OrganizationMembership;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class CreateOrganization
{
    public function __construct(
        private OrganizationAccess $access,
        private AuditRecorder $audit,
    ) {}

    public function execute(User $actor, CreateOrganizationData $data): Organization
    {
        return DB::transaction(function () use ($actor, $data): Organization {
            $person = Person::query()
                ->where('user_id', $actor->getKey())
                ->lockForUpdate()
                ->first();

            if ($person === null) {
                throw new DomainRuleViolation(
                    errorCode: 'identity.person_profile_required',
                    message: 'Crie seu perfil de pessoa antes de criar uma organização.',
                    httpStatus: 409,
                );
            }

            if ($data->parentOrganizationId !== null) {
                $parent = Organization::query()->findOrFail($data->parentOrganizationId);

                if (! $this->access->canManage($actor, $parent)) {
                    throw new AuthorizationException;
                }
            }

            $organization = Organization::query()->create([
                'name' => $data->name,
                'slug' => $data->slug,
                'type' => $data->type,
                'parent_organization_id' => $data->parentOrganizationId,
                'status' => OrganizationStatus::Active,
                'visibility' => $data->visibility,
                'timezone' => $data->timezone,
                'created_by' => $actor->getKey(),
            ]);

            $membership = OrganizationMembership::query()->create([
                'organization_id' => $organization->getKey(),
                'person_id' => $person->getKey(),
                'role' => MembershipRole::Owner,
                'status' => MembershipStatus::Active,
                'joined_at' => now(),
                'created_by' => $actor->getKey(),
            ]);

            $actorId = (string) $actor->getKey();
            $organizationId = (string) $organization->getKey();

            $this->audit->record(
                actorUserId: $actorId,
                organizationId: $organizationId,
                action: AuditAction::OrganizationCreated,
                entityType: 'organization',
                entityId: $organizationId,
                previousState: null,
                newState: [
                    'name' => $data->name,
                    'type' => $data->type->value,
                    'visibility' => $data->visibility->value,
                    'parent_organization_id' => $data->parentOrganizationId,
                ],
            );

            $this->audit->record(
                actorUserId: $actorId,
                organizationId: $organizationId,
                action: AuditAction::MembershipGranted,
                entityType: 'organization_membership',
                entityId: (string) $membership->getKey(),
                previousState: null,
                newState: [
                    'person_id' => (string) $person->getKey(),
                    'role' => MembershipRole::Owner->value,
                    'status' => MembershipStatus::Active->value,
                ],
            );

            return $organization->setRelation('memberships', collect([$membership]));
        });
    }
}

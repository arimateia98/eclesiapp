<?php

namespace App\Modules\Organizations\Application\Actions;

use App\Modules\Identity\Domain\Enums\PersonStatus;
use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Application\DTOs\AddOrganizationMemberData;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Models\OrganizationMembership;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class AddOrganizationMember
{
    public function __construct(
        private OrganizationAccess $access,
        private AuditRecorder $audit,
    ) {}

    public function execute(
        User $actor,
        Organization $organization,
        AddOrganizationMemberData $data,
    ): OrganizationMembership {
        if (! $this->access->canAssignRole($actor, $organization, $data->role)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $organization, $data): OrganizationMembership {
            $person = Person::query()->create([
                'full_name' => $data->person->fullName,
                'preferred_name' => $data->person->preferredName,
                'email' => $data->person->email,
                'phone' => $data->person->phone,
                'status' => PersonStatus::Active,
                'created_by' => $actor->getKey(),
            ]);

            $membership = OrganizationMembership::query()->create([
                'organization_id' => $organization->getKey(),
                'person_id' => $person->getKey(),
                'role' => $data->role,
                'status' => MembershipStatus::Active,
                'joined_at' => now(),
                'created_by' => $actor->getKey(),
            ]);

            $this->audit->record(
                actorUserId: (string) $actor->getKey(),
                organizationId: (string) $organization->getKey(),
                action: AuditAction::MembershipGranted,
                entityType: 'organization_membership',
                entityId: (string) $membership->getKey(),
                previousState: null,
                newState: [
                    'person_id' => (string) $person->getKey(),
                    'role' => $data->role->value,
                    'status' => MembershipStatus::Active->value,
                ],
            );

            return $membership;
        });
    }
}

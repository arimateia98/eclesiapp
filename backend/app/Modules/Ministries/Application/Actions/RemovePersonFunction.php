<?php

namespace App\Modules\Ministries\Application\Actions;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Ministries\Domain\Models\PersonFunction;
use App\Modules\Ministries\Domain\Models\ServiceFunction;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class RemovePersonFunction
{
    public function __construct(
        private OrganizationAccess $access,
        private AuditRecorder $audit,
    ) {}

    public function execute(
        User $actor,
        Organization $organization,
        string $personId,
        string $serviceFunctionId,
    ): void {
        if (! $this->access->canManageMembers($actor, $organization)) {
            throw new AuthorizationException;
        }

        DB::transaction(function () use ($actor, $organization, $personId, $serviceFunctionId): void {
            $isActiveMember = $organization->memberships()
                ->where('person_id', $personId)
                ->where('status', MembershipStatus::Active)
                ->lockForUpdate()
                ->exists();

            $serviceFunctionExists = ServiceFunction::query()
                ->whereKey($serviceFunctionId)
                ->where('organization_id', $organization->getKey())
                ->lockForUpdate()
                ->exists();

            if (! $isActiveMember || ! $serviceFunctionExists) {
                throw new DomainRuleViolation(
                    errorCode: 'ministries.person_function_not_found',
                    message: 'A função atribuída não foi encontrada nesta organização.',
                    httpStatus: 404,
                );
            }

            $deleted = PersonFunction::query()
                ->where('organization_id', $organization->getKey())
                ->where('person_id', $personId)
                ->where('service_function_id', $serviceFunctionId)
                ->delete();

            if ($deleted !== 1) {
                throw new DomainRuleViolation(
                    errorCode: 'ministries.person_function_not_found',
                    message: 'A função atribuída não foi encontrada nesta organização.',
                    httpStatus: 404,
                );
            }

            $this->audit->record(
                actorUserId: (string) $actor->getKey(),
                organizationId: (string) $organization->getKey(),
                action: AuditAction::PersonFunctionRemoved,
                entityType: 'person_function',
                entityId: "{$personId}:{$serviceFunctionId}",
                previousState: [
                    'person_id' => $personId,
                    'service_function_id' => $serviceFunctionId,
                    'assigned' => true,
                ],
                newState: ['assigned' => false],
            );
        });
    }
}

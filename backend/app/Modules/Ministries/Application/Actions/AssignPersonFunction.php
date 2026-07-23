<?php

namespace App\Modules\Ministries\Application\Actions;

use App\Modules\Identity\Domain\Enums\PersonStatus;
use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Ministries\Application\DTOs\AssignPersonFunctionData;
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

final readonly class AssignPersonFunction
{
    public function __construct(
        private OrganizationAccess $access,
        private AuditRecorder $audit,
    ) {}

    public function execute(
        User $actor,
        Organization $organization,
        string $personId,
        AssignPersonFunctionData $data,
    ): PersonFunction {
        if (! $this->access->canManageMembers($actor, $organization)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $organization, $personId, $data): PersonFunction {
            $person = Person::query()
                ->whereKey($personId)
                ->where('status', PersonStatus::Active)
                ->whereHas('memberships', fn ($query) => $query
                    ->where('organization_id', $organization->getKey())
                    ->where('status', MembershipStatus::Active))
                ->lockForUpdate()
                ->first();

            if ($person === null) {
                throw new DomainRuleViolation(
                    errorCode: 'ministries.person_not_active_member',
                    message: 'A pessoa precisa ser membro ativo desta organização.',
                );
            }

            $serviceFunction = ServiceFunction::query()
                ->with('ministryType')
                ->whereKey($data->serviceFunctionId)
                ->where('organization_id', $organization->getKey())
                ->where('active', true)
                ->whereHas('ministryType', fn ($query) => $query->where('active', true))
                ->lockForUpdate()
                ->first();

            if ($serviceFunction === null) {
                throw new DomainRuleViolation(
                    errorCode: 'ministries.service_function_unavailable',
                    message: 'A função de serviço informada não está ativa nesta organização.',
                );
            }

            if (PersonFunction::query()
                ->where('person_id', $person->getKey())
                ->where('service_function_id', $serviceFunction->getKey())
                ->exists()) {
                throw new DomainRuleViolation(
                    errorCode: 'ministries.person_function_already_assigned',
                    message: 'Esta função já está atribuída à pessoa.',
                    httpStatus: 409,
                );
            }

            $assignment = PersonFunction::query()->create([
                'organization_id' => $organization->getKey(),
                'person_id' => $person->getKey(),
                'service_function_id' => $serviceFunction->getKey(),
                'assigned_by' => $actor->getKey(),
            ]);
            $assignment->setRelation('serviceFunction', $serviceFunction);

            $this->audit->record(
                actorUserId: (string) $actor->getKey(),
                organizationId: (string) $organization->getKey(),
                action: AuditAction::PersonFunctionAssigned,
                entityType: 'person_function',
                entityId: sprintf('%s:%s', $person->getKey(), $serviceFunction->getKey()),
                previousState: null,
                newState: [
                    'person_id' => (string) $person->getKey(),
                    'service_function_id' => (string) $serviceFunction->getKey(),
                    'assigned' => true,
                ],
            );

            return $assignment;
        });
    }
}

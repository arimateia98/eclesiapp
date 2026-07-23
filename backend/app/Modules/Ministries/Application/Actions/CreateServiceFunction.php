<?php

namespace App\Modules\Ministries\Application\Actions;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Ministries\Application\DTOs\CreateServiceFunctionData;
use App\Modules\Ministries\Domain\Models\MinistryType;
use App\Modules\Ministries\Domain\Models\ServiceFunction;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateServiceFunction
{
    public function __construct(
        private OrganizationAccess $access,
        private AuditRecorder $audit,
    ) {}

    public function execute(
        User $actor,
        Organization $organization,
        CreateServiceFunctionData $data,
    ): ServiceFunction {
        if (! $this->access->canManage($actor, $organization)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $organization, $data): ServiceFunction {
            Organization::query()->whereKey($organization->getKey())->lockForUpdate()->firstOrFail();

            $ministryType = MinistryType::query()
                ->whereKey($data->ministryTypeId)
                ->where('organization_id', $organization->getKey())
                ->where('active', true)
                ->lockForUpdate()
                ->first();

            if ($ministryType === null) {
                throw new DomainRuleViolation(
                    errorCode: 'ministries.ministry_type_unavailable',
                    message: 'O tipo de ministério informado não está ativo nesta organização.',
                );
            }

            $slug = Str::slug(trim($data->name));

            if ($slug === '') {
                throw new DomainRuleViolation(
                    errorCode: 'ministries.service_function_name_invalid',
                    message: 'Informe um nome com letras ou números para a função de serviço.',
                );
            }

            if (ServiceFunction::query()
                ->where('organization_id', $organization->getKey())
                ->where('ministry_type_id', $ministryType->getKey())
                ->where('slug', $slug)
                ->exists()) {
                throw new DomainRuleViolation(
                    errorCode: 'ministries.service_function_already_exists',
                    message: 'Já existe uma função com este nome no tipo de ministério.',
                    httpStatus: 409,
                );
            }

            $serviceFunction = ServiceFunction::query()->create([
                'organization_id' => $organization->getKey(),
                'ministry_type_id' => $ministryType->getKey(),
                'name' => trim($data->name),
                'slug' => $slug,
                'active' => true,
                'created_by' => $actor->getKey(),
            ]);

            $this->audit->record(
                actorUserId: (string) $actor->getKey(),
                organizationId: (string) $organization->getKey(),
                action: AuditAction::ServiceFunctionCreated,
                entityType: 'service_function',
                entityId: (string) $serviceFunction->getKey(),
                previousState: null,
                newState: [
                    'ministry_type_id' => (string) $ministryType->getKey(),
                    'name' => $serviceFunction->name,
                    'slug' => $serviceFunction->slug,
                    'active' => true,
                ],
            );

            return $serviceFunction->setRelation('ministryType', $ministryType);
        });
    }
}

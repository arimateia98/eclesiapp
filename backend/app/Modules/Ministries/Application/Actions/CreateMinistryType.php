<?php

namespace App\Modules\Ministries\Application\Actions;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Ministries\Application\DTOs\CreateMinistryTypeData;
use App\Modules\Ministries\Domain\Models\MinistryType;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateMinistryType
{
    public function __construct(
        private OrganizationAccess $access,
        private AuditRecorder $audit,
    ) {}

    public function execute(
        User $actor,
        Organization $organization,
        CreateMinistryTypeData $data,
    ): MinistryType {
        if (! $this->access->canManage($actor, $organization)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $organization, $data): MinistryType {
            Organization::query()->whereKey($organization->getKey())->lockForUpdate()->firstOrFail();

            $slug = Str::slug(trim($data->name));

            if ($slug === '') {
                throw new DomainRuleViolation(
                    errorCode: 'ministries.ministry_type_name_invalid',
                    message: 'Informe um nome com letras ou números para o tipo de ministério.',
                );
            }

            if (MinistryType::query()
                ->where('organization_id', $organization->getKey())
                ->where('slug', $slug)
                ->exists()) {
                throw new DomainRuleViolation(
                    errorCode: 'ministries.ministry_type_already_exists',
                    message: 'Já existe um tipo de ministério com este nome na organização.',
                    httpStatus: 409,
                );
            }

            $ministryType = MinistryType::query()->create([
                'organization_id' => $organization->getKey(),
                'name' => trim($data->name),
                'slug' => $slug,
                'description' => $data->description,
                'active' => true,
                'created_by' => $actor->getKey(),
            ]);

            $this->audit->record(
                actorUserId: (string) $actor->getKey(),
                organizationId: (string) $organization->getKey(),
                action: AuditAction::MinistryTypeCreated,
                entityType: 'ministry_type',
                entityId: (string) $ministryType->getKey(),
                previousState: null,
                newState: [
                    'name' => $ministryType->name,
                    'slug' => $ministryType->slug,
                    'active' => true,
                ],
            );

            return $ministryType;
        });
    }
}

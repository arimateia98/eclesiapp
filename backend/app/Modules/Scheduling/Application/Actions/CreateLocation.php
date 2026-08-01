<?php

namespace App\Modules\Scheduling\Application\Actions;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;
use App\Modules\Scheduling\Application\DTOs\CreateLocationData;
use App\Modules\Scheduling\Domain\Models\Location;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateLocation
{
    public function __construct(
        private OrganizationAccess $access,
        private AuditRecorder $audit,
    ) {}

    public function execute(User $actor, Organization $organization, CreateLocationData $data): Location
    {
        if (! $this->access->canManage($actor, $organization)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $organization, $data): Location {
            Organization::query()->whereKey($organization->getKey())->lockForUpdate()->firstOrFail();
            $slug = Str::slug(trim($data->name));

            if ($slug === '') {
                throw new DomainRuleViolation(
                    errorCode: 'scheduling.location_name_invalid',
                    message: 'Informe um nome válido para o local.',
                );
            }

            if (Location::query()
                ->where('organization_id', $organization->getKey())
                ->where('slug', $slug)
                ->exists()) {
                throw new DomainRuleViolation(
                    errorCode: 'scheduling.location_already_exists',
                    message: 'Já existe um local com este nome na organização.',
                    httpStatus: 409,
                );
            }

            $location = Location::query()->create([
                'organization_id' => $organization->getKey(),
                'name' => trim($data->name),
                'slug' => $slug,
                'address_line' => $data->addressLine,
                'city' => $data->city,
                'timezone' => $data->timezone,
                'active' => true,
                'created_by' => $actor->getKey(),
            ]);

            $this->audit->record(
                actorUserId: (string) $actor->getKey(),
                organizationId: (string) $organization->getKey(),
                action: AuditAction::LocationCreated,
                entityType: 'location',
                entityId: (string) $location->getKey(),
                previousState: null,
                newState: [
                    'name' => $location->name,
                    'slug' => $slug,
                    'timezone' => $location->timezone,
                    'active' => true,
                ],
            );

            return $location;
        });
    }
}

<?php

namespace App\Modules\Organizations\Application\DTOs;

use App\Modules\Organizations\Domain\Enums\OrganizationRelationshipType;

final readonly class CreateOrganizationRelationshipData
{
    public function __construct(
        public string $targetOrganizationId,
        public OrganizationRelationshipType $type,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            targetOrganizationId: (string) $data['target_organization_id'],
            type: OrganizationRelationshipType::from((string) $data['relationship_type']),
        );
    }
}

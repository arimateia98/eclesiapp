<?php

namespace App\Modules\Organizations\Application\DTOs;

use App\Modules\Organizations\Domain\Enums\OrganizationType;
use App\Modules\Organizations\Domain\Enums\OrganizationVisibility;

final readonly class CreateOrganizationData
{
    public function __construct(
        public string $name,
        public string $slug,
        public OrganizationType $type,
        public OrganizationVisibility $visibility,
        public string $timezone,
        public ?string $parentOrganizationId,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            type: OrganizationType::from((string) $data['type']),
            visibility: OrganizationVisibility::from((string) $data['visibility']),
            timezone: (string) $data['timezone'],
            parentOrganizationId: isset($data['parent_organization_id'])
                ? (string) $data['parent_organization_id']
                : null,
        );
    }
}

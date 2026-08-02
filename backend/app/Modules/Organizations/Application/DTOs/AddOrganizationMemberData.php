<?php

namespace App\Modules\Organizations\Application\DTOs;

use App\Modules\Identity\Application\DTOs\CreatePersonData;
use App\Modules\Organizations\Domain\Enums\MembershipRole;

final readonly class AddOrganizationMemberData
{
    public function __construct(
        public CreatePersonData $person,
        public MembershipRole $role,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            person: CreatePersonData::fromArray($data),
            role: MembershipRole::from((string) $data['role']),
        );
    }
}

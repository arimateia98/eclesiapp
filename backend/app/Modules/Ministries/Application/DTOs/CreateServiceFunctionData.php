<?php

namespace App\Modules\Ministries\Application\DTOs;

final readonly class CreateServiceFunctionData
{
    public function __construct(
        public string $ministryTypeId,
        public string $name,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            ministryTypeId: (string) $data['ministry_type_id'],
            name: (string) $data['name'],
        );
    }
}

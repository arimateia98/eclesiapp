<?php

namespace App\Modules\Ministries\Application\DTOs;

final readonly class CreateMinistryTypeData
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            description: isset($data['description']) ? (string) $data['description'] : null,
        );
    }
}

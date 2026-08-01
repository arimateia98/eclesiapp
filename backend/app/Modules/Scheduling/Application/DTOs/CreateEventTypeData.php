<?php

namespace App\Modules\Scheduling\Application\DTOs;

final readonly class CreateEventTypeData
{
    public function __construct(public string $name) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(name: (string) $data['name']);
    }
}

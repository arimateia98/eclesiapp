<?php

namespace App\Modules\Scheduling\Application\DTOs;

final readonly class CreateLocationData
{
    public function __construct(
        public string $name,
        public ?string $addressLine,
        public ?string $city,
        public string $timezone,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            addressLine: isset($data['address_line']) ? (string) $data['address_line'] : null,
            city: isset($data['city']) ? (string) $data['city'] : null,
            timezone: (string) $data['timezone'],
        );
    }
}

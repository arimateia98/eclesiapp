<?php

namespace App\Modules\Scheduling\Application\DTOs;

use Carbon\CarbonImmutable;

final readonly class CreateEventData
{
    public function __construct(
        public string $eventTypeId,
        public ?string $locationId,
        public string $title,
        public ?string $description,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            eventTypeId: (string) $data['event_type_id'],
            locationId: isset($data['location_id']) ? (string) $data['location_id'] : null,
            title: (string) $data['title'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            startsAt: CarbonImmutable::parse((string) $data['starts_at'])->utc(),
            endsAt: CarbonImmutable::parse((string) $data['ends_at'])->utc(),
        );
    }
}

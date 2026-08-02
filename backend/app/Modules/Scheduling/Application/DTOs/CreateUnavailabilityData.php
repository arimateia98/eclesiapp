<?php

namespace App\Modules\Scheduling\Application\DTOs;

use Carbon\CarbonImmutable;

final readonly class CreateUnavailabilityData
{
    public function __construct(public CarbonImmutable $startsAt, public CarbonImmutable $endsAt) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            CarbonImmutable::parse((string) $data['starts_at'])->utc(),
            CarbonImmutable::parse((string) $data['ends_at'])->utc(),
        );
    }
}

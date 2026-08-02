<?php

namespace App\Modules\Scheduling\Application\DTOs;

final readonly class CreateAssignmentData
{
    public function __construct(public string $missionSlotId, public string $personId) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self((string) $data['mission_slot_id'], (string) $data['person_id']);
    }
}

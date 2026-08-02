<?php

namespace App\Modules\Missions\Application\DTOs;

final readonly class CreateInternalMissionData
{
    /** @param list<CreateMissionSlotData> $slots */
    public function __construct(
        public string $ministryTypeId,
        public string $title,
        public ?string $description,
        public array $slots,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        /** @var list<array<string, mixed>> $slots */
        $slots = $data['slots'];

        return new self(
            ministryTypeId: (string) $data['ministry_type_id'],
            title: (string) $data['title'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            slots: array_map(CreateMissionSlotData::fromArray(...), $slots),
        );
    }
}

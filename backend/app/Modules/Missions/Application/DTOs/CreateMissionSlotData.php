<?php

namespace App\Modules\Missions\Application\DTOs;

final readonly class CreateMissionSlotData
{
    public function __construct(
        public string $serviceFunctionId,
        public int $quantity,
        public bool $required,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            serviceFunctionId: (string) $data['service_function_id'],
            quantity: (int) $data['quantity'],
            required: (bool) ($data['required'] ?? true),
        );
    }
}

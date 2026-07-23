<?php

namespace App\Modules\Ministries\Application\DTOs;

final readonly class AssignPersonFunctionData
{
    public function __construct(public string $serviceFunctionId) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(serviceFunctionId: (string) $data['service_function_id']);
    }
}

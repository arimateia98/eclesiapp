<?php

namespace App\Modules\Identity\Application\DTOs;

final readonly class CreatePersonData
{
    public function __construct(
        public string $fullName,
        public ?string $preferredName,
        public ?string $email,
        public ?string $phone,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            fullName: (string) $data['full_name'],
            preferredName: isset($data['preferred_name']) ? (string) $data['preferred_name'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
        );
    }
}

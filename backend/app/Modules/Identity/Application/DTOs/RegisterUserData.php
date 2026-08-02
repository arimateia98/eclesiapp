<?php

namespace App\Modules\Identity\Application\DTOs;

final readonly class RegisterUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $fullName,
        public ?string $preferredName,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            email: (string) $data['email'],
            password: (string) $data['password'],
            fullName: (string) $data['full_name'],
            preferredName: isset($data['preferred_name']) ? (string) $data['preferred_name'] : null,
        );
    }
}

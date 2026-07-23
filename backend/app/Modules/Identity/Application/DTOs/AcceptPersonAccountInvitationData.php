<?php

namespace App\Modules\Identity\Application\DTOs;

final readonly class AcceptPersonAccountInvitationData
{
    public function __construct(
        public string $token,
        public string $name,
        public string $password,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            token: (string) $data['token'],
            name: (string) $data['name'],
            password: (string) $data['password'],
        );
    }
}

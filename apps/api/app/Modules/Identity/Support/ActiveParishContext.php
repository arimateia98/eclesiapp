<?php

declare(strict_types=1);

namespace App\Modules\Identity\Support;

use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\ParishUserMembership;

final readonly class ActiveParishContext
{
    public const REQUEST_ATTRIBUTE = 'active_parish_context';

    public const SESSION_KEY = 'active_parish_id';

    /** @param list<string> $roleCodes */
    public function __construct(
        public Parish $parish,
        public ParishUserMembership $membership,
        public array $roleCodes,
    ) {}

    /** @return array{parish: array{id: string, name: string, timezone: string}, roles: list<string>} */
    public function toArray(): array
    {
        return [
            'parish' => [
                'id' => $this->parish->id,
                'name' => $this->parish->name,
                'timezone' => $this->parish->timezone,
            ],
            'roles' => $this->roleCodes,
        ];
    }
}

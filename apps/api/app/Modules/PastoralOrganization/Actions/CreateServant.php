<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Actions;

use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use App\Modules\PastoralOrganization\Models\Servant;
use Illuminate\Support\Facades\DB;

final class CreateServant
{
    /** @param array{full_name: string, preferred_name?: string|null, phone?: string|null, email?: string|null, joined_on?: string|null} $data */
    public function execute(Parish $parish, User $actor, array $data): Servant
    {
        return DB::transaction(function () use ($parish, $actor, $data): Servant {
            $person = Person::query()->create([
                'full_name' => trim($data['full_name']),
                'preferred_name' => $this->nullableTrim($data['preferred_name'] ?? null),
                'phone' => $this->nullableTrim($data['phone'] ?? null),
                'email' => $this->nullableTrim($data['email'] ?? null),
            ]);

            return Servant::query()->create([
                'parish_id' => $parish->id,
                'person_id' => $person->id,
                'status' => 'ACTIVE',
                'joined_on' => $data['joined_on'] ?? now()->toDateString(),
                'created_by_user_id' => $actor->id,
            ])->load('person');
        });
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}

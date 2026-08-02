<?php

namespace App\Modules\Scheduling\Application\Actions;

use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Scheduling\Application\DTOs\CreateUnavailabilityData;
use App\Modules\Scheduling\Domain\Models\Unavailability;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Support\Facades\DB;

final class CreateOwnUnavailability
{
    public function execute(User $actor, CreateUnavailabilityData $data): Unavailability
    {
        return DB::transaction(function () use ($actor, $data): Unavailability {
            $person = Person::query()->where('user_id', $actor->getKey())->lockForUpdate()->first();
            if ($person === null) {
                throw new DomainRuleViolation('scheduling.person_profile_required', 'É necessário possuir um perfil de pessoa para informar indisponibilidade.');
            }

            return Unavailability::query()->create([
                'person_id' => $person->getKey(),
                'starts_at' => $data->startsAt,
                'ends_at' => $data->endsAt,
            ]);
        });
    }
}

<?php

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Application\DTOs\CreatePersonData;
use App\Modules\Identity\Domain\Enums\PersonStatus;
use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Support\Facades\DB;

final class CreatePersonProfile
{
    public function execute(User $user, CreatePersonData $data): Person
    {
        return DB::transaction(function () use ($user, $data): Person {
            $lockedUser = User::query()
                ->whereKey((string) $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->person()->exists()) {
                throw new DomainRuleViolation(
                    errorCode: 'identity.person_already_linked',
                    message: 'Este usuário já possui uma pessoa vinculada.',
                    httpStatus: 409,
                );
            }

            return Person::query()->create([
                'user_id' => $lockedUser->getKey(),
                'full_name' => $data->fullName,
                'preferred_name' => $data->preferredName,
                'email' => $data->email,
                'phone' => $data->phone,
                'status' => PersonStatus::Active,
                'created_by' => $lockedUser->getKey(),
            ]);
        });
    }
}

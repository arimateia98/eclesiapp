<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Exceptions\RegistrationFailed;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final class RegisterUser
{
    public function execute(string $fullName, ?string $preferredName, string $email, string $password): User
    {
        $normalizedEmail = mb_strtolower(trim($email));

        return DB::transaction(function () use ($fullName, $preferredName, $normalizedEmail, $password): User {
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['registration:'.$normalizedEmail]);

            if (User::query()->where('login_email', $normalizedEmail)->exists()) {
                throw new RegistrationFailed('Não foi possível criar a conta com os dados informados.');
            }

            $person = Person::query()->create([
                'full_name' => trim($fullName),
                'preferred_name' => $preferredName !== null && trim($preferredName) !== ''
                    ? trim($preferredName)
                    : null,
                'email' => $normalizedEmail,
            ]);

            return User::query()->create([
                'person_id' => $person->id,
                'login_email' => $normalizedEmail,
                'password_hash' => $password,
                'auth_provider' => 'LOCAL',
                'status' => 'ACTIVE',
                'last_login_at' => now(),
            ]);
        });
    }
}

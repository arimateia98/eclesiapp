<?php

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Application\DTOs\RegisterUserData;
use App\Modules\Identity\Domain\Enums\PersonStatus;
use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RegisterUser
{
    public function execute(RegisterUserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $email = Str::lower(trim($data->email));
            $user = User::query()->create([
                'name' => $data->name,
                'email' => $email,
                'password' => $data->password,
            ]);

            Person::query()->create([
                'user_id' => $user->getKey(),
                'full_name' => $data->fullName,
                'preferred_name' => $data->preferredName,
                'email' => $email,
                'status' => PersonStatus::Active,
                'created_by' => $user->getKey(),
            ]);

            return $user;
        });
    }
}

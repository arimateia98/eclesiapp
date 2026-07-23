<?php

namespace Database\Seeders;

use App\Modules\Identity\Application\Actions\RegisterUser;
use App\Modules\Identity\Application\DTOs\RegisterUserData;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(RegisterUser::class)->execute(new RegisterUserData(
            name: 'Test User',
            email: 'test@example.com',
            password: 'password',
            fullName: 'Test User',
            preferredName: null,
        ));
    }
}

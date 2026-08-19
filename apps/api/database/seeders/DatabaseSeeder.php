<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Identity\Models\Role;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'PARISH_PRIEST' => 'Pároco',
            'PARISH_ADMIN' => 'Administrador paroquial',
            'PARISH_VIEWER' => 'Consulta paroquial',
        ] as $code => $name) {
            Role::query()->updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}

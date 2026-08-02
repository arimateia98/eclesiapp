<?php

namespace Tests\Feature\Scheduling;

use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class PostgresAssignmentLockTest extends TestCase
{
    use DatabaseMigrations;

    public function test_person_row_lock_serializes_competing_assignment_transactions(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste exige PostgreSQL real.');
        }

        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $personId = (string) $this->postJson('/api/v1/profile', ['full_name' => 'Pessoa concorrente'])
            ->assertCreated()->json('data.id');

        /** @var array<string, mixed> $connection */
        $connection = Config::get('database.connections.pgsql');
        Config::set('database.connections.assignment_lock_probe', $connection);
        DB::purge('assignment_lock_probe');

        DB::connection()->beginTransaction();
        DB::connection('assignment_lock_probe')->beginTransaction();

        try {
            Person::query()->whereKey($personId)->lockForUpdate()->firstOrFail();
            DB::connection('assignment_lock_probe')->statement("SET LOCAL lock_timeout = '200ms'");

            $this->expectException(QueryException::class);
            Person::on('assignment_lock_probe')->whereKey($personId)->lockForUpdate()->firstOrFail();
        } finally {
            DB::connection('assignment_lock_probe')->rollBack();
            DB::connection()->rollBack();
            DB::purge('assignment_lock_probe');
        }
    }
}

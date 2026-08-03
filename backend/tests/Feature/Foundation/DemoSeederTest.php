<?php

namespace Tests\Feature\Foundation;

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_demo_scenario_is_complete_and_idempotent(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');

        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', ['email' => 'coordenador@eclesiapp.local']);
        $this->assertDatabaseHas('users', ['email' => 'servo@eclesiapp.local']);
        $this->assertDatabaseCount('organizations', 1);
        $this->assertDatabaseCount('organization_memberships', 2);
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseHas('events', ['title' => 'Missa de demonstração', 'status' => 'published']);
        $this->assertDatabaseCount('assignments', 1);
        $this->assertDatabaseHas('assignments', ['status' => 'confirmed']);
        $this->assertDatabaseCount('unavailabilities', 1);
    }
}

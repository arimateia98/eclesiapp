<?php

namespace Tests\Feature\Scheduling;

use App\Modules\Identity\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class UnavailabilityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_servo_informs_only_interval_and_coordinator_can_consult_it(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $personId = (string) $this->postJson('/api/v1/profile', ['full_name' => 'Servo'])->assertCreated()->json('data.id');
        $organizationId = (string) $this->postJson('/api/v1/organizations', [
            'name' => 'Comunidade', 'slug' => 'comunidade-indisponibilidade', 'type' => 'community',
            'visibility' => 'private', 'timezone' => 'America/Fortaleza',
        ])->assertCreated()->json('data.id');

        $id = (string) $this->postJson('/api/v1/me/unavailabilities', [
            'starts_at' => '2026-08-10T19:00:00-03:00',
            'ends_at' => '2026-08-10T21:00:00-03:00',
            'note' => 'não deve ser aceito nem exposto',
        ])->assertCreated()
            ->assertJsonMissingPath('data.note')
            ->assertJsonPath('data.starts_at', '2026-08-10T22:00:00.000000Z')
            ->json('data.id');

        $this->getJson('/api/v1/me/unavailabilities')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/organizations/{$organizationId}/members/{$personId}/unavailabilities")
            ->assertOk()->assertJsonPath('data.0.id', $id);
        $this->deleteJson("/api/v1/me/unavailabilities/{$id}")->assertNoContent();
    }

    public function test_interval_must_end_after_it_starts(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/profile', ['full_name' => 'Servo'])->assertCreated();

        $this->postJson('/api/v1/me/unavailabilities', [
            'starts_at' => '2026-08-10T21:00:00-03:00',
            'ends_at' => '2026-08-10T19:00:00-03:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('ends_at');
    }
}

<?php

namespace Tests\Feature\Identity;

use App\Modules\Identity\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class PersonProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/profile')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'auth.unauthenticated');
    }

    public function test_user_can_create_one_separate_person_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = [
            'full_name' => 'Maria de Nazaré',
            'preferred_name' => 'Maria',
            'email' => 'maria@example.test',
            'phone' => '+55 85 99999-9999',
        ];

        $this->postJson('/api/v1/profile', $payload)
            ->assertCreated()
            ->assertJsonPath('data.full_name', 'Maria de Nazaré')
            ->assertJsonPath('data.has_user', true);

        $this->assertDatabaseHas('people', [
            'user_id' => $user->getKey(),
            'full_name' => 'Maria de Nazaré',
        ]);

        $this->postJson('/api/v1/profile', $payload)
            ->assertConflict()
            ->assertJsonPath('code', 'identity.person_already_linked');
    }
}

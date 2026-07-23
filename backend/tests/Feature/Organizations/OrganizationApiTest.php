<?php

namespace Tests\Feature\Organizations;

use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Enums\MembershipRole;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_creation_requires_person_profile(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/organizations', $this->organizationPayload('sem-perfil'))
            ->assertConflict()
            ->assertJsonPath('code', 'identity.person_profile_required');

        $this->assertDatabaseCount('organizations', 0);
        $this->assertDatabaseCount('organization_memberships', 0);
    }

    public function test_organization_is_created_with_owner_membership_atomically(): void
    {
        $user = $this->authenticatedUserWithProfile('Ana Proprietária');

        $response = $this->postJson('/api/v1/organizations', $this->organizationPayload('comunidade-sao-jose'))
            ->assertCreated()
            ->assertJsonPath('data.slug', 'comunidade-sao-jose')
            ->assertJsonPath('data.current_user_role', 'owner');

        $organizationId = (string) $response->json('data.id');

        $this->assertDatabaseHas('organization_memberships', [
            'organization_id' => $organizationId,
            'person_id' => $user->person()->firstOrFail()->getKey(),
            'role' => MembershipRole::Owner->value,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organizationId,
            'action' => AuditAction::OrganizationCreated->value,
            'entity_type' => 'organization',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organizationId,
            'action' => AuditAction::MembershipGranted->value,
            'entity_type' => 'organization_membership',
        ]);
    }

    public function test_private_organizations_are_isolated_and_public_ones_are_listed(): void
    {
        $owner = $this->authenticatedUserWithProfile('Primeiro Dono');
        $ownPrivateId = (string) $this->postJson(
            '/api/v1/organizations',
            $this->organizationPayload('privada-do-primeiro'),
        )->assertCreated()->json('data.id');

        $otherOwner = $this->authenticatedUserWithProfile('Segundo Dono');
        $otherPrivateId = (string) $this->postJson(
            '/api/v1/organizations',
            $this->organizationPayload('privada-do-segundo'),
        )->assertCreated()->json('data.id');
        $publicId = (string) $this->postJson(
            '/api/v1/organizations',
            $this->organizationPayload('publica-do-segundo', 'public'),
        )->assertCreated()->json('data.id');

        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/organizations')
            ->assertOk()
            ->assertJsonFragment(['id' => $ownPrivateId])
            ->assertJsonFragment(['id' => $publicId])
            ->assertJsonMissing(['id' => $otherPrivateId]);

        $this->getJson("/api/v1/organizations/{$otherPrivateId}")
            ->assertForbidden()
            ->assertJsonPath('code', 'auth.forbidden');

        Sanctum::actingAs($otherOwner);
        $this->getJson("/api/v1/organizations/{$otherPrivateId}")->assertOk();
    }

    public function test_coordinator_can_add_person_without_user_but_cannot_assign_owner_role(): void
    {
        $owner = $this->authenticatedUserWithProfile('Dono');
        $organizationId = (string) $this->postJson(
            '/api/v1/organizations',
            $this->organizationPayload('ministerio-teste'),
        )->assertCreated()->json('data.id');

        $coordinatorPersonId = (string) $this->postJson("/api/v1/organizations/{$organizationId}/members", [
            'full_name' => 'Coordenador',
            'email' => 'coordenador@example.test',
            'role' => 'coordinator',
        ])->assertCreated()->json('data.person.id');

        $this->getJson("/api/v1/organizations/{$organizationId}/members")
            ->assertOk()
            ->assertJsonFragment([
                'id' => $coordinatorPersonId,
                'email' => 'coordenador@example.test',
            ]);

        $coordinator = User::factory()->create(['email' => 'coordenador@example.test']);
        $this->assertDatabaseHas('people', ['id' => $coordinatorPersonId, 'user_id' => null]);
        Person::query()->findOrFail($coordinatorPersonId)->update(['user_id' => $coordinator->getKey()]);

        Sanctum::actingAs($coordinator);

        $this->postJson("/api/v1/organizations/{$organizationId}/members", [
            'full_name' => 'Servo sem conta',
            'role' => 'member',
        ])->assertCreated()
            ->assertJsonPath('data.person.has_user', false);

        $audit = AuditLog::query()
            ->where('organization_id', $organizationId)
            ->where('action', AuditAction::MembershipGranted)
            ->latest('created_at')
            ->firstOrFail();

        $newState = $audit->getAttribute('new_state');
        self::assertIsArray($newState);
        self::assertSame('member', $newState['role']);
        self::assertArrayNotHasKey('email', $newState);
        self::assertArrayNotHasKey('phone', $newState);

        $peopleBeforeEscalation = $this->getConnection()->table('people')->count();

        $this->postJson("/api/v1/organizations/{$organizationId}/members", [
            'full_name' => 'Proprietário indevido',
            'role' => 'owner',
        ])->assertForbidden()
            ->assertJsonPath('code', 'auth.forbidden');

        self::assertSame($peopleBeforeEscalation, $this->getConnection()->table('people')->count());
        self::assertTrue(Organization::query()->whereKey($organizationId)->exists());
    }

    private function authenticatedUserWithProfile(string $name): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/profile', ['full_name' => $name])->assertCreated();

        return $user;
    }

    /** @return array<string, string> */
    private function organizationPayload(string $slug, string $visibility = 'private'): array
    {
        return [
            'name' => str_replace('-', ' ', ucfirst($slug)),
            'slug' => $slug,
            'type' => 'community',
            'visibility' => $visibility,
            'timezone' => 'America/Fortaleza',
        ];
    }
}

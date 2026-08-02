<?php

namespace Tests\Feature\Organizations;

use App\Modules\Identity\Domain\Models\User;
use App\Shared\Auditing\AuditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class OrganizationRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_of_both_organizations_can_create_one_active_relationship(): void
    {
        $this->authenticateWithProfile('Gestor');
        $sourceId = $this->createOrganization('ministerio-independente', 'ministry');
        $targetId = $this->createOrganization('comunidade-servida', 'community');

        $payload = [
            'target_organization_id' => $targetId,
            'relationship_type' => 'serves_at',
        ];

        $this->postJson("/api/v1/organizations/{$sourceId}/relationships", $payload)
            ->assertCreated()
            ->assertJsonPath('data.relationship_type', 'serves_at');

        $this->postJson("/api/v1/organizations/{$sourceId}/relationships", $payload)
            ->assertConflict()
            ->assertJsonPath('code', 'organizations.relationship_already_active');

        $this->assertDatabaseCount('organization_relationships', 1);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $sourceId,
            'action' => AuditAction::RelationshipCreated->value,
            'entity_type' => 'organization_relationship',
        ]);
    }

    public function test_relationship_rejects_self_reference_and_cross_organization_management(): void
    {
        $firstOwner = $this->authenticateWithProfile('Primeiro gestor');
        $firstOrganizationId = $this->createOrganization('primeira-organizacao');

        $this->postJson("/api/v1/organizations/{$firstOrganizationId}/relationships", [
            'target_organization_id' => $firstOrganizationId,
            'relationship_type' => 'linked_to',
        ])->assertUnprocessable()
            ->assertJsonPath('code', 'organizations.relationship_self_reference');

        $this->authenticateWithProfile('Segundo gestor');
        $secondOrganizationId = $this->createOrganization('segunda-organizacao');

        $this->postJson("/api/v1/organizations/{$secondOrganizationId}/relationships", [
            'target_organization_id' => $firstOrganizationId,
            'relationship_type' => 'partner_of',
        ])->assertForbidden()
            ->assertJsonPath('code', 'auth.forbidden');

        Sanctum::actingAs($firstOwner);
        $this->assertDatabaseCount('organization_relationships', 0);
    }

    private function authenticateWithProfile(string $name): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/profile', ['full_name' => $name])->assertCreated();

        return $user;
    }

    private function createOrganization(string $slug, string $type = 'community'): string
    {
        return (string) $this->postJson('/api/v1/organizations', [
            'name' => str_replace('-', ' ', ucfirst($slug)),
            'slug' => $slug,
            'type' => $type,
            'visibility' => 'private',
            'timezone' => 'America/Fortaleza',
        ])->assertCreated()->json('data.id');
    }
}

<?php

namespace Tests\Feature\Ministries;

use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use App\Shared\Auditing\AuditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MinistryCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_manages_organization_scoped_ministry_catalog(): void
    {
        $this->authenticatedUserWithProfile('Proprietária');
        $organizationId = $this->createOrganization('comunidade-sao-jose');

        $ministryTypeId = (string) $this->postJson(
            "/api/v1/organizations/{$organizationId}/ministry-types",
            ['name' => 'Música', 'description' => 'Serviço musical das celebrações.'],
        )->assertCreated()
            ->assertJsonPath('data.slug', 'musica')
            ->json('data.id');

        $serviceFunctionId = (string) $this->postJson(
            "/api/v1/organizations/{$organizationId}/service-functions",
            ['ministry_type_id' => $ministryTypeId, 'name' => 'Vocalista'],
        )->assertCreated()
            ->assertJsonPath('data.ministry_type.id', $ministryTypeId)
            ->assertJsonPath('data.slug', 'vocalista')
            ->json('data.id');

        $this->getJson("/api/v1/organizations/{$organizationId}/ministry-types")
            ->assertOk()
            ->assertJsonFragment(['id' => $ministryTypeId, 'name' => 'Música']);

        $this->getJson("/api/v1/organizations/{$organizationId}/service-functions")
            ->assertOk()
            ->assertJsonFragment(['id' => $serviceFunctionId, 'name' => 'Vocalista']);

        $this->postJson(
            "/api/v1/organizations/{$organizationId}/ministry-types",
            ['name' => 'Música'],
        )->assertConflict()
            ->assertJsonPath('code', 'ministries.ministry_type_already_exists');

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organizationId,
            'action' => AuditAction::MinistryTypeCreated->value,
            'entity_id' => $ministryTypeId,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organizationId,
            'action' => AuditAction::ServiceFunctionCreated->value,
            'entity_id' => $serviceFunctionId,
        ]);
    }

    public function test_coordinator_assigns_and_removes_person_function_but_cannot_change_catalog(): void
    {
        $this->authenticatedUserWithProfile('Proprietário');
        $organizationId = $this->createOrganization('paroquia-central');
        [$ministryTypeId, $serviceFunctionId] = $this->createCatalog($organizationId);
        $coordinatorPersonId = $this->addMember($organizationId, 'Coordenadora', 'coordinator');
        $memberPersonId = $this->addMember($organizationId, 'Pessoa servidora', 'member');

        $coordinator = User::factory()->create(['email' => 'coordenadora@example.test']);
        Person::query()->findOrFail($coordinatorPersonId)->update([
            'user_id' => $coordinator->getKey(),
            'email' => $coordinator->email,
        ]);
        Sanctum::actingAs($coordinator);

        $this->postJson(
            "/api/v1/organizations/{$organizationId}/ministry-types",
            ['name' => 'Acolhida'],
        )->assertForbidden();

        $this->postJson(
            "/api/v1/organizations/{$organizationId}/members/{$memberPersonId}/functions",
            ['service_function_id' => $serviceFunctionId],
        )->assertCreated()
            ->assertJsonPath('data.person_id', $memberPersonId)
            ->assertJsonPath('data.service_function.ministry_type.id', $ministryTypeId);

        $this->getJson(
            "/api/v1/organizations/{$organizationId}/members/{$memberPersonId}/functions",
        )->assertOk()
            ->assertJsonFragment(['id' => $serviceFunctionId, 'name' => 'Leitor']);

        $this->postJson(
            "/api/v1/organizations/{$organizationId}/members/{$memberPersonId}/functions",
            ['service_function_id' => $serviceFunctionId],
        )->assertConflict()
            ->assertJsonPath('code', 'ministries.person_function_already_assigned');

        $this->deleteJson(
            "/api/v1/organizations/{$organizationId}/members/{$memberPersonId}/functions/{$serviceFunctionId}",
        )->assertNoContent();

        $this->assertDatabaseMissing('person_functions', [
            'person_id' => $memberPersonId,
            'service_function_id' => $serviceFunctionId,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organizationId,
            'action' => AuditAction::PersonFunctionAssigned->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organizationId,
            'action' => AuditAction::PersonFunctionRemoved->value,
        ]);
    }

    public function test_person_functions_cannot_cross_organization_boundaries(): void
    {
        $this->authenticatedUserWithProfile('Proprietário comum');
        $firstOrganizationId = $this->createOrganization('primeira-comunidade');
        $secondOrganizationId = $this->createOrganization('segunda-comunidade');
        $personId = $this->addMember($firstOrganizationId, 'Membro da primeira', 'member');
        [, $otherFunctionId] = $this->createCatalog($secondOrganizationId);

        $this->postJson(
            "/api/v1/organizations/{$firstOrganizationId}/members/{$personId}/functions",
            ['service_function_id' => $otherFunctionId],
        )->assertUnprocessable()
            ->assertJsonPath('code', 'ministries.service_function_unavailable');

        $this->assertDatabaseCount('person_functions', 0);
    }

    private function authenticatedUserWithProfile(string $name): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/profile', ['full_name' => $name])->assertCreated();

        return $user;
    }

    private function createOrganization(string $slug): string
    {
        return (string) $this->postJson('/api/v1/organizations', [
            'name' => str_replace('-', ' ', ucfirst($slug)),
            'slug' => $slug,
            'type' => 'community',
            'visibility' => 'private',
            'timezone' => 'America/Fortaleza',
        ])->assertCreated()->json('data.id');
    }

    private function addMember(string $organizationId, string $name, string $role): string
    {
        return (string) $this->postJson("/api/v1/organizations/{$organizationId}/members", [
            'full_name' => $name,
            'role' => $role,
        ])->assertCreated()->json('data.person.id');
    }

    /** @return array{string, string} */
    private function createCatalog(string $organizationId): array
    {
        $ministryTypeId = (string) $this->postJson(
            "/api/v1/organizations/{$organizationId}/ministry-types",
            ['name' => 'Liturgia'],
        )->assertCreated()->json('data.id');
        $serviceFunctionId = (string) $this->postJson(
            "/api/v1/organizations/{$organizationId}/service-functions",
            ['ministry_type_id' => $ministryTypeId, 'name' => 'Leitor'],
        )->assertCreated()->json('data.id');

        return [$ministryTypeId, $serviceFunctionId];
    }
}

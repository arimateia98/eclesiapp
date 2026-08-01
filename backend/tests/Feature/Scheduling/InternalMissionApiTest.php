<?php

namespace Tests\Feature\Scheduling;

use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use App\Shared\Auditing\AuditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class InternalMissionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_creates_an_internal_event_and_mission_with_person_slots(): void
    {
        $this->authenticatedUserWithProfile('Proprietária');
        $organizationId = $this->createOrganization('comunidade-sao-jose');
        [$ministryTypeId, $serviceFunctionId] = $this->createMinistryCatalog($organizationId);

        $eventTypeId = (string) $this->postJson(
            "/api/v1/organizations/{$organizationId}/event-types",
            ['name' => 'Missa'],
        )->assertCreated()
            ->assertJsonPath('data.slug', 'missa')
            ->json('data.id');

        $locationId = (string) $this->postJson(
            "/api/v1/organizations/{$organizationId}/locations",
            [
                'name' => 'Igreja Matriz',
                'address_line' => 'Praça da Matriz, 10',
                'city' => 'Fortaleza',
                'timezone' => 'America/Fortaleza',
            ],
        )->assertCreated()
            ->assertJsonPath('data.slug', 'igreja-matriz')
            ->json('data.id');

        $eventId = (string) $this->postJson(
            "/api/v1/organizations/{$organizationId}/events",
            [
                'event_type_id' => $eventTypeId,
                'location_id' => $locationId,
                'title' => 'Missa de Nossa Senhora',
                'starts_at' => '2026-08-15T19:00:00-03:00',
                'ends_at' => '2026-08-15T20:30:00-03:00',
            ],
        )->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.visibility', 'private')
            ->assertJsonPath('data.starts_at', '2026-08-15T22:00:00.000000Z')
            ->assertJsonPath('data.event_type.id', $eventTypeId)
            ->assertJsonPath('data.location.id', $locationId)
            ->json('data.id');

        $missionId = (string) $this->postJson(
            "/api/v1/organizations/{$organizationId}/events/{$eventId}/missions",
            [
                'ministry_type_id' => $ministryTypeId,
                'title' => 'Equipe de leitores',
                'description' => 'Escala interna para a celebração.',
                'slots' => [[
                    'service_function_id' => $serviceFunctionId,
                    'quantity' => 2,
                    'required' => true,
                ]],
            ],
        )->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.visibility', 'private')
            ->assertJsonPath('data.participation_policy', 'coordinator_assignment')
            ->assertJsonPath('data.slots.0.slot_type', 'person')
            ->assertJsonPath('data.slots.0.quantity', 2)
            ->assertJsonPath('data.slots.0.service_function.id', $serviceFunctionId)
            ->json('data.id');

        $this->getJson("/api/v1/organizations/{$organizationId}/events")
            ->assertOk()
            ->assertJsonFragment(['id' => $eventId, 'title' => 'Missa de Nossa Senhora']);
        $this->getJson("/api/v1/organizations/{$organizationId}/events/{$eventId}")
            ->assertOk()
            ->assertJsonPath('data.missions.0.id', $missionId);
        $this->getJson("/api/v1/organizations/{$organizationId}/events/{$eventId}/missions")
            ->assertOk()
            ->assertJsonPath('data.0.id', $missionId);

        foreach ([
            AuditAction::EventTypeCreated,
            AuditAction::LocationCreated,
            AuditAction::EventCreated,
            AuditAction::MissionCreated,
        ] as $action) {
            $this->assertDatabaseHas('audit_logs', [
                'organization_id' => $organizationId,
                'action' => $action->value,
            ]);
        }
    }

    public function test_coordinator_plans_events_and_missions_but_cannot_change_catalogs(): void
    {
        $this->authenticatedUserWithProfile('Proprietário');
        $organizationId = $this->createOrganization('paroquia-central');
        [$ministryTypeId, $serviceFunctionId] = $this->createMinistryCatalog($organizationId);
        $eventTypeId = $this->createEventType($organizationId, 'Celebração');
        $coordinatorPersonId = $this->addMember($organizationId, 'Coordenadora', 'coordinator');
        $coordinator = User::factory()->create(['email' => 'coordenadora-agenda@example.test']);
        Person::query()->findOrFail($coordinatorPersonId)->update([
            'user_id' => $coordinator->getKey(),
            'email' => $coordinator->email,
        ]);
        Sanctum::actingAs($coordinator);

        $this->postJson(
            "/api/v1/organizations/{$organizationId}/event-types",
            ['name' => 'Formação'],
        )->assertForbidden();
        $this->postJson(
            "/api/v1/organizations/{$organizationId}/locations",
            ['name' => 'Salão', 'timezone' => 'America/Fortaleza'],
        )->assertForbidden();

        $eventId = (string) $this->postJson(
            "/api/v1/organizations/{$organizationId}/events",
            [
                'event_type_id' => $eventTypeId,
                'title' => 'Celebração dominical',
                'starts_at' => '2026-08-16T18:00:00-03:00',
                'ends_at' => '2026-08-16T19:00:00-03:00',
            ],
        )->assertCreated()->json('data.id');

        $this->postJson(
            "/api/v1/organizations/{$organizationId}/events/{$eventId}/missions",
            [
                'ministry_type_id' => $ministryTypeId,
                'title' => 'Leitura',
                'slots' => [[
                    'service_function_id' => $serviceFunctionId,
                    'quantity' => 1,
                ]],
            ],
        )->assertCreated();
    }

    public function test_scheduling_resources_cannot_cross_organization_boundaries(): void
    {
        $this->authenticatedUserWithProfile('Proprietário comum');
        $firstOrganizationId = $this->createOrganization('primeira-comunidade');
        $secondOrganizationId = $this->createOrganization('segunda-comunidade');
        [$firstMinistryTypeId] = $this->createMinistryCatalog($firstOrganizationId);
        [, $secondServiceFunctionId] = $this->createMinistryCatalog($secondOrganizationId);
        $firstEventTypeId = $this->createEventType($firstOrganizationId, 'Missa');
        $secondEventTypeId = $this->createEventType($secondOrganizationId, 'Celebração');
        $secondLocationId = $this->createLocation($secondOrganizationId, 'Capela');

        $this->postJson(
            "/api/v1/organizations/{$firstOrganizationId}/events",
            $this->eventPayload($secondEventTypeId),
        )->assertUnprocessable()
            ->assertJsonPath('code', 'scheduling.event_type_unavailable');

        $this->postJson(
            "/api/v1/organizations/{$firstOrganizationId}/events",
            $this->eventPayload($firstEventTypeId, $secondLocationId),
        )->assertUnprocessable()
            ->assertJsonPath('code', 'scheduling.location_unavailable');

        $eventId = (string) $this->postJson(
            "/api/v1/organizations/{$firstOrganizationId}/events",
            $this->eventPayload($firstEventTypeId),
        )->assertCreated()->json('data.id');

        $this->postJson(
            "/api/v1/organizations/{$firstOrganizationId}/events/{$eventId}/missions",
            [
                'ministry_type_id' => $firstMinistryTypeId,
                'title' => 'Vaga cruzada',
                'slots' => [[
                    'service_function_id' => $secondServiceFunctionId,
                    'quantity' => 1,
                ]],
            ],
        )->assertUnprocessable()
            ->assertJsonPath('code', 'missions.service_function_unavailable');

        $this->getJson("/api/v1/organizations/{$secondOrganizationId}/events/{$eventId}")
            ->assertNotFound();
        $this->assertDatabaseCount('missions', 0);
    }

    public function test_member_can_read_but_cannot_plan_an_internal_schedule(): void
    {
        $this->authenticatedUserWithProfile('Proprietário');
        $organizationId = $this->createOrganization('comunidade-acolhedora');
        $eventTypeId = $this->createEventType($organizationId, 'Missa');
        $memberPersonId = $this->addMember($organizationId, 'Membro', 'member');
        $member = User::factory()->create(['email' => 'membro-agenda@example.test']);
        Person::query()->findOrFail($memberPersonId)->update([
            'user_id' => $member->getKey(),
            'email' => $member->email,
        ]);
        Sanctum::actingAs($member);

        $this->getJson("/api/v1/organizations/{$organizationId}/events")->assertOk();
        $this->postJson(
            "/api/v1/organizations/{$organizationId}/events",
            $this->eventPayload($eventTypeId),
        )->assertForbidden();
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
    private function createMinistryCatalog(string $organizationId): array
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

    private function createEventType(string $organizationId, string $name): string
    {
        return (string) $this->postJson(
            "/api/v1/organizations/{$organizationId}/event-types",
            ['name' => $name],
        )->assertCreated()->json('data.id');
    }

    private function createLocation(string $organizationId, string $name): string
    {
        return (string) $this->postJson(
            "/api/v1/organizations/{$organizationId}/locations",
            ['name' => $name, 'timezone' => 'America/Fortaleza'],
        )->assertCreated()->json('data.id');
    }

    /** @return array<string, string> */
    private function eventPayload(string $eventTypeId, ?string $locationId = null): array
    {
        return array_filter([
            'event_type_id' => $eventTypeId,
            'location_id' => $locationId,
            'title' => 'Evento interno',
            'starts_at' => '2026-08-20T19:00:00-03:00',
            'ends_at' => '2026-08-20T20:00:00-03:00',
        ], fn (?string $value): bool => $value !== null);
    }
}

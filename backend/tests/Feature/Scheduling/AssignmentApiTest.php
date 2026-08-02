<?php

namespace Tests\Feature\Scheduling;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Enums\MembershipRole;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Models\OrganizationMembership;
use App\Shared\Auditing\AuditAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_coordinator_assigns_qualified_member_and_assignment_is_audited(): void
    {
        $this->authenticatedUserWithProfile('Proprietário');
        $organizationId = $this->createOrganization('comunidade-escalas');
        [$ministryTypeId, $serviceFunctionId] = $this->createCatalog($organizationId);
        $personId = $this->addMember($organizationId, 'Maria Leitora');
        $this->assignFunction($organizationId, $personId, $serviceFunctionId);
        [$eventId, $missionId, $slotId] = $this->createSchedule(
            $organizationId,
            $ministryTypeId,
            $serviceFunctionId,
            '2026-08-10T19:00:00-03:00',
            '2026-08-10T20:00:00-03:00',
        );

        $this->getJson($this->eligibleMembersUrl($organizationId, $eventId, $missionId, $slotId))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.person.id', $personId);

        $assignmentId = (string) $this->postJson(
            $this->assignmentsUrl($organizationId, $eventId, $missionId),
            ['mission_slot_id' => $slotId, 'person_id' => $personId],
        )->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.person.id', $personId)
            ->assertJsonPath('data.mission_slot.id', $slotId)
            ->json('data.id');

        $this->getJson($this->assignmentsUrl($organizationId, $eventId, $missionId))
            ->assertOk()->assertJsonPath('data.0.id', $assignmentId);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organizationId,
            'action' => AuditAction::AssignmentCreated->value,
            'entity_id' => $assignmentId,
        ]);
    }

    public function test_assignment_requires_qualification_and_respects_slot_capacity(): void
    {
        $this->authenticatedUserWithProfile('Proprietário');
        $organizationId = $this->createOrganization('paroquia-capacidade');
        [$ministryTypeId, $serviceFunctionId] = $this->createCatalog($organizationId);
        $qualifiedId = $this->addMember($organizationId, 'Pessoa qualificada');
        $unqualifiedId = $this->addMember($organizationId, 'Pessoa sem função');
        $otherQualifiedId = $this->addMember($organizationId, 'Outra pessoa qualificada');
        $this->assignFunction($organizationId, $qualifiedId, $serviceFunctionId);
        $this->assignFunction($organizationId, $otherQualifiedId, $serviceFunctionId);
        [$eventId, $missionId, $slotId] = $this->createSchedule(
            $organizationId, $ministryTypeId, $serviceFunctionId,
            '2026-08-11T19:00:00-03:00', '2026-08-11T20:00:00-03:00',
        );
        $url = $this->assignmentsUrl($organizationId, $eventId, $missionId);

        $this->getJson($this->eligibleMembersUrl($organizationId, $eventId, $missionId, $slotId))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['id' => $unqualifiedId]);
        $otherOrganizationId = $this->createOrganization('outra-organizacao-elegibilidade');
        $this->getJson($this->eligibleMembersUrl($otherOrganizationId, $eventId, $missionId, $slotId))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'scheduling.mission_slot_unavailable');

        $this->postJson($url, ['mission_slot_id' => $slotId, 'person_id' => $unqualifiedId])
            ->assertUnprocessable()->assertJsonPath('code', 'scheduling.person_not_qualified');
        $this->postJson($url, ['mission_slot_id' => $slotId, 'person_id' => $qualifiedId])->assertCreated();
        $this->postJson($url, ['mission_slot_id' => $slotId, 'person_id' => $otherQualifiedId])
            ->assertConflict()->assertJsonPath('code', 'scheduling.mission_slot_full');
    }

    public function test_overlapping_assignment_is_rejected_for_same_person_across_organizations(): void
    {
        $this->authenticatedUserWithProfile('Proprietário comum');
        $firstOrganizationId = $this->createOrganization('primeira-agenda');
        $secondOrganizationId = $this->createOrganization('segunda-agenda');
        [$firstMinistryId, $firstFunctionId] = $this->createCatalog($firstOrganizationId);
        [$secondMinistryId, $secondFunctionId] = $this->createCatalog($secondOrganizationId);
        $personId = $this->addMember($firstOrganizationId, 'Pessoa compartilhada');
        $this->assignFunction($firstOrganizationId, $personId, $firstFunctionId);
        OrganizationMembership::query()->create([
            'organization_id' => $secondOrganizationId,
            'person_id' => $personId,
            'role' => MembershipRole::Member,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);
        $this->assignFunction($secondOrganizationId, $personId, $secondFunctionId);

        [$firstEventId, $firstMissionId, $firstSlotId] = $this->createSchedule(
            $firstOrganizationId, $firstMinistryId, $firstFunctionId,
            '2026-08-12T19:00:00-03:00', '2026-08-12T20:30:00-03:00',
        );
        [$secondEventId, $secondMissionId, $secondSlotId] = $this->createSchedule(
            $secondOrganizationId, $secondMinistryId, $secondFunctionId,
            '2026-08-12T20:00:00-03:00', '2026-08-12T21:00:00-03:00',
        );

        $this->postJson($this->assignmentsUrl($firstOrganizationId, $firstEventId, $firstMissionId), [
            'mission_slot_id' => $firstSlotId, 'person_id' => $personId,
        ])->assertCreated();
        $this->postJson($this->assignmentsUrl($secondOrganizationId, $secondEventId, $secondMissionId), [
            'mission_slot_id' => $secondSlotId, 'person_id' => $personId,
        ])->assertConflict()->assertJsonPath('code', 'scheduling.assignment_time_conflict');
        $this->assertDatabaseCount('assignments', 1);
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
            'name' => $slug, 'slug' => $slug, 'type' => 'community',
            'visibility' => 'private', 'timezone' => 'America/Fortaleza',
        ])->assertCreated()->json('data.id');
    }

    /** @return array{string, string} */
    private function createCatalog(string $organizationId): array
    {
        $ministryId = (string) $this->postJson("/api/v1/organizations/{$organizationId}/ministry-types", ['name' => 'Liturgia'])
            ->assertCreated()->json('data.id');
        $functionId = (string) $this->postJson("/api/v1/organizations/{$organizationId}/service-functions", [
            'ministry_type_id' => $ministryId, 'name' => 'Leitor',
        ])->assertCreated()->json('data.id');

        return [$ministryId, $functionId];
    }

    private function addMember(string $organizationId, string $name): string
    {
        return (string) $this->postJson("/api/v1/organizations/{$organizationId}/members", [
            'full_name' => $name, 'role' => 'member',
        ])->assertCreated()->json('data.person.id');
    }

    private function assignFunction(string $organizationId, string $personId, string $functionId): void
    {
        $this->postJson("/api/v1/organizations/{$organizationId}/members/{$personId}/functions", [
            'service_function_id' => $functionId,
        ])->assertCreated();
    }

    /** @return array{string, string, string} */
    private function createSchedule(string $organizationId, string $ministryId, string $functionId, string $startsAt, string $endsAt): array
    {
        $eventTypeId = (string) $this->postJson("/api/v1/organizations/{$organizationId}/event-types", ['name' => 'Missa'])
            ->assertCreated()->json('data.id');
        $eventId = (string) $this->postJson("/api/v1/organizations/{$organizationId}/events", [
            'event_type_id' => $eventTypeId, 'title' => 'Celebração',
            'starts_at' => $startsAt, 'ends_at' => $endsAt,
        ])->assertCreated()->json('data.id');
        $mission = $this->postJson("/api/v1/organizations/{$organizationId}/events/{$eventId}/missions", [
            'ministry_type_id' => $ministryId, 'title' => 'Leitura',
            'slots' => [['service_function_id' => $functionId, 'quantity' => 1]],
        ])->assertCreated();

        return [$eventId, (string) $mission->json('data.id'), (string) $mission->json('data.slots.0.id')];
    }

    private function assignmentsUrl(string $organizationId, string $eventId, string $missionId): string
    {
        return "/api/v1/organizations/{$organizationId}/events/{$eventId}/missions/{$missionId}/assignments";
    }

    private function eligibleMembersUrl(string $organizationId, string $eventId, string $missionId, string $slotId): string
    {
        return "/api/v1/organizations/{$organizationId}/events/{$eventId}/missions/{$missionId}/slots/{$slotId}/eligible-members";
    }
}

<?php

namespace Tests\Feature\Identity;

use App\Modules\Identity\Domain\Enums\AccountInvitationStatus;
use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\PersonAccountInvitation;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Infrastructure\Mail\PersonAccountInvitationMail;
use App\Shared\Auditing\AuditAction;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class PersonAccountInvitationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_manager_can_invite_person_and_invitation_links_new_account_once(): void
    {
        Mail::fake();
        [$owner, $organizationId] = $this->ownerAndOrganization('gestor-convites');
        $personId = $this->addPerson($organizationId, 'Convidada', 'convidada@example.test');

        $this->postJson("/api/v1/organizations/{$organizationId}/members/{$personId}/account-invitations")
            ->assertAccepted()
            ->assertJsonPath('data.status', AccountInvitationStatus::Pending->value);

        $plainToken = '';
        Mail::assertQueued(
            PersonAccountInvitationMail::class,
            function (PersonAccountInvitationMail $mail) use (&$plainToken): bool {
                parse_str((string) parse_url($mail->acceptanceUrl, PHP_URL_QUERY), $query);
                $plainToken = is_string($query['invitation'] ?? null) ? $query['invitation'] : '';

                return $mail->hasTo('convidada@example.test');
            },
        );

        self::assertSame(64, strlen($plainToken));
        $this->assertDatabaseMissing('person_account_invitations', ['token_hash' => $plainToken]);

        $response = $this->postJson('/api/v1/auth/account-invitations/accept', [
            'token' => $plainToken,
            'name' => 'Convidada',
            'password' => 'senha-segura',
            'password_confirmation' => 'senha-segura',
            'device_name' => 'painel-web',
        ])->assertCreated()
            ->assertJsonPath('data.user.email', 'convidada@example.test');

        $linkedUserId = (string) $response->json('data.user.id');
        $this->assertDatabaseHas('people', ['id' => $personId, 'user_id' => $linkedUserId]);
        $this->assertDatabaseHas('users', [
            'id' => $linkedUserId,
            'email' => 'convidada@example.test',
        ]);
        $this->assertDatabaseHas('person_account_invitations', [
            'person_id' => $personId,
            'status' => AccountInvitationStatus::Accepted->value,
            'accepted_by_user_id' => $linkedUserId,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organizationId,
            'action' => AuditAction::PersonAccountLinked->value,
            'entity_type' => 'person',
            'entity_id' => $personId,
        ]);

        $this->postJson('/api/v1/auth/account-invitations/accept', [
            'token' => $plainToken,
            'name' => 'Outra pessoa',
            'password' => 'senha-segura',
            'password_confirmation' => 'senha-segura',
            'device_name' => 'painel-web',
        ])->assertUnprocessable()
            ->assertJsonPath('code', 'identity.account_invitation_invalid');

        self::assertNotSame($owner->getKey(), $linkedUserId);
    }

    public function test_expired_invitation_and_cross_organization_access_are_rejected(): void
    {
        Mail::fake();
        [, $firstOrganizationId] = $this->ownerAndOrganization('primeira-gestao');
        $personId = $this->addPerson($firstOrganizationId, 'Pessoa Restrita', 'restrita@example.test');

        [, $secondOrganizationId] = $this->ownerAndOrganization('segunda-gestao');

        $this->postJson("/api/v1/organizations/{$firstOrganizationId}/members/{$personId}/account-invitations")
            ->assertForbidden()
            ->assertJsonPath('code', 'auth.forbidden');

        Sanctum::actingAs(User::query()->whereHas('person.memberships', fn ($query) => $query
            ->where('organization_id', $firstOrganizationId))->firstOrFail());

        $this->postJson("/api/v1/organizations/{$firstOrganizationId}/members/{$personId}/account-invitations")
            ->assertAccepted();

        $plainToken = '';
        Mail::assertQueued(
            PersonAccountInvitationMail::class,
            function (PersonAccountInvitationMail $mail) use (&$plainToken): bool {
                parse_str((string) parse_url($mail->acceptanceUrl, PHP_URL_QUERY), $query);
                $plainToken = is_string($query['invitation'] ?? null) ? $query['invitation'] : '';

                return true;
            },
        );

        PersonAccountInvitation::query()->where('person_id', $personId)->update(['expires_at' => now()->subMinute()]);

        $this->postJson('/api/v1/auth/account-invitations/accept', [
            'token' => $plainToken,
            'name' => 'Pessoa Restrita',
            'password' => 'senha-segura',
            'password_confirmation' => 'senha-segura',
            'device_name' => 'painel-web',
        ])->assertUnprocessable()
            ->assertJsonPath('code', 'identity.account_invitation_invalid');

        self::assertNull(Person::query()->findOrFail($personId)->user_id);
        self::assertNotSame($firstOrganizationId, $secondOrganizationId);
    }

    /** @return array{User, string} */
    private function ownerAndOrganization(string $slug): array
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/profile', ['full_name' => "Gestor {$slug}"])->assertCreated();

        $organizationId = (string) $this->postJson('/api/v1/organizations', [
            'name' => "Organização {$slug}",
            'slug' => $slug,
            'type' => 'community',
            'visibility' => 'private',
            'timezone' => 'America/Fortaleza',
        ])->assertCreated()->json('data.id');

        return [$owner, $organizationId];
    }

    private function addPerson(string $organizationId, string $name, string $email): string
    {
        return (string) $this->postJson("/api/v1/organizations/{$organizationId}/members", [
            'full_name' => $name,
            'email' => $email,
            'role' => 'member',
        ])->assertCreated()->json('data.person.id');
    }
}

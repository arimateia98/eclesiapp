<?php

declare(strict_types=1);

use App\Modules\EcclesialStructure\Models\Diocese;
use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\ParishUserMembership;
use App\Modules\Identity\Models\ParishUserRole;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\ActiveParishContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function activeParishUser(): User
{
    $person = Person::query()->create(['full_name' => 'José Operário']);

    return User::query()->create([
        'person_id' => $person->id,
        'login_email' => 'jose@example.com',
        'password_hash' => 'senha-segura',
        'status' => 'ACTIVE',
    ]);
}

/** @return array{0: Parish, 1: Parish} */
function twoActiveParishes(): array
{
    $diocese = Diocese::query()->create([
        'name' => 'Diocese de Contexto',
        'timezone' => 'America/Fortaleza',
    ]);

    return [
        Parish::query()->create([
            'diocese_id' => $diocese->id,
            'name' => 'Paróquia São José',
            'timezone' => 'America/Fortaleza',
        ]),
        Parish::query()->create([
            'diocese_id' => $diocese->id,
            'name' => 'Paróquia Santa Rita',
            'timezone' => 'America/Fortaleza',
        ]),
    ];
}

function activateMembership(User $user, Parish $parish): ParishUserMembership
{
    return ParishUserMembership::query()->create([
        'parish_id' => $parish->id,
        'user_id' => $user->id,
        'status' => 'ACTIVE',
        'joined_at' => now(),
    ]);
}

it('stores only an authorized active parish in the web session', function (): void {
    $user = activeParishUser();
    [$parish] = twoActiveParishes();
    activateMembership($user, $parish);

    $this->actingAs($user)
        ->putJson('/api/v1/session/active-parish', ['parish_id' => $parish->id])
        ->assertOk()
        ->assertSessionHas(ActiveParishContext::SESSION_KEY, $parish->id)
        ->assertJsonPath('data.parish.id', $parish->id);

    $this->getJson('/api/v1/active-parish')
        ->assertOk()
        ->assertJsonPath('data.parish.id', $parish->id);
});

it('does not reveal or select a parish without an active membership', function (): void {
    $user = activeParishUser();
    [$authorizedParish, $externalParish] = twoActiveParishes();
    activateMembership($user, $authorizedParish);

    $this->actingAs($user)
        ->putJson('/api/v1/session/active-parish', ['parish_id' => $externalParish->id])
        ->assertForbidden()
        ->assertJsonPath('code', 'PARISH_ACCESS_DENIED')
        ->assertSessionMissing(ActiveParishContext::SESSION_KEY);
});

it('requires an explicit context when the user has multiple active parishes', function (): void {
    $user = activeParishUser();
    [$firstParish, $secondParish] = twoActiveParishes();
    activateMembership($user, $firstParish);
    activateMembership($user, $secondParish);

    $this->actingAs($user)
        ->getJson('/api/v1/active-parish')
        ->assertConflict()
        ->assertJsonPath('code', 'PARISH_CONTEXT_REQUIRED');
});

it('keeps an account without parish valid but denies parish-scoped resources', function (): void {
    $user = activeParishUser();

    $this->actingAs($user)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonCount(0, 'data.parishes')
        ->assertJsonPath('data.active_parish_id', null);

    $this->getJson('/api/v1/active-parish')
        ->assertForbidden()
        ->assertJsonPath('code', 'NO_ACTIVE_PARISH_MEMBERSHIP');
});

it('automatically resolves the only active parish', function (): void {
    $user = activeParishUser();
    [$parish] = twoActiveParishes();
    activateMembership($user, $parish);

    $this->actingAs($user)
        ->getJson('/api/v1/active-parish')
        ->assertOk()
        ->assertJsonPath('data.parish.id', $parish->id);
});

it('accepts an authorized parish header for stateless clients', function (): void {
    $user = activeParishUser();
    [$firstParish, $secondParish] = twoActiveParishes();
    activateMembership($user, $firstParish);
    activateMembership($user, $secondParish);

    $this->actingAs($user)
        ->withHeader('X-Parish-Id', $secondParish->id)
        ->getJson('/api/v1/active-parish')
        ->assertOk()
        ->assertJsonPath('data.parish.id', $secondParish->id);
});

it('returns only roles that are effective in the active parish today', function (): void {
    $user = activeParishUser();
    [$parish] = twoActiveParishes();
    activateMembership($user, $parish);
    $administrator = Role::query()->create(['code' => 'PARISH_ADMIN', 'name' => 'Administrador paroquial']);
    $expired = Role::query()->create(['code' => 'EXPIRED_ROLE', 'name' => 'Papel expirado']);

    ParishUserRole::query()->create([
        'parish_id' => $parish->id,
        'user_id' => $user->id,
        'role_id' => $administrator->id,
        'starts_on' => now()->subDay()->toDateString(),
    ]);
    ParishUserRole::query()->create([
        'parish_id' => $parish->id,
        'user_id' => $user->id,
        'role_id' => $expired->id,
        'starts_on' => now()->subDays(10)->toDateString(),
        'ends_on' => now()->subDay()->toDateString(),
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/active-parish')
        ->assertOk()
        ->assertJsonPath('data.roles', ['PARISH_ADMIN']);
});

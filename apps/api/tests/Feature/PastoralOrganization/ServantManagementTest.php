<?php

declare(strict_types=1);

use App\Modules\EcclesialStructure\Models\Diocese;
use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\ParishUserMembership;
use App\Modules\Identity\Models\ParishUserRole;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\PastoralOrganization\Models\Servant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return array{user: User, parish: Parish} */
function parishManager(string $roleCode = 'PARISH_ADMIN'): array
{
    $person = Person::query()->create(['full_name' => 'Responsável Paroquial']);
    $user = User::query()->create([
        'person_id' => $person->id,
        'login_email' => strtolower($roleCode).'@example.com',
        'password_hash' => 'senha-segura',
        'status' => 'ACTIVE',
    ]);
    $diocese = Diocese::query()->create([
        'name' => 'Diocese dos Servos',
        'timezone' => 'America/Fortaleza',
    ]);
    $parish = Parish::query()->create([
        'diocese_id' => $diocese->id,
        'name' => 'Paróquia dos Servos',
        'timezone' => 'America/Fortaleza',
    ]);
    $role = Role::query()->create(['code' => $roleCode, 'name' => $roleCode]);

    ParishUserMembership::query()->create([
        'parish_id' => $parish->id,
        'user_id' => $user->id,
        'status' => 'ACTIVE',
    ]);
    ParishUserRole::query()->create([
        'parish_id' => $parish->id,
        'user_id' => $user->id,
        'role_id' => $role->id,
        'starts_on' => now()->toDateString(),
    ]);

    return ['user' => $user, 'parish' => $parish];
}

it('lets an authorized parish administrator create a servant without creating a user', function (): void {
    ['user' => $administrator, 'parish' => $parish] = parishManager();

    $this->actingAs($administrator)
        ->withHeader('X-Parish-Id', $parish->id)
        ->postJson("/api/v1/parishes/{$parish->id}/servants", [
            'full_name' => 'João Leitor',
            'preferred_name' => 'João',
            'phone' => '85999999999',
        ])
        ->assertCreated()
        ->assertJsonPath('data.person.full_name', 'João Leitor')
        ->assertJsonPath('data.has_user', false)
        ->assertJsonPath('data.status', 'ACTIVE');

    $servant = Servant::query()->with('person.user')->sole();
    expect($servant->created_by_user_id)->toBe($administrator->id)
        ->and($servant->person?->user)->toBeNull()
        ->and(User::query()->count())->toBe(1);
});

it('allows priests to list servants only from their active parish', function (): void {
    ['user' => $priest, 'parish' => $parish] = parishManager('PARISH_PRIEST');
    $servantPerson = Person::query()->create(['full_name' => 'Servo Visível']);
    Servant::query()->create([
        'parish_id' => $parish->id,
        'person_id' => $servantPerson->id,
        'status' => 'ACTIVE',
        'created_by_user_id' => $priest->id,
    ]);

    $this->actingAs($priest)
        ->withHeader('X-Parish-Id', $parish->id)
        ->getJson("/api/v1/parishes/{$parish->id}/servants?search=Visível")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.person.full_name', 'Servo Visível');
});

it('denies servant management to a viewer', function (): void {
    ['user' => $viewer, 'parish' => $parish] = parishManager('PARISH_VIEWER');

    $this->actingAs($viewer)
        ->withHeader('X-Parish-Id', $parish->id)
        ->postJson("/api/v1/parishes/{$parish->id}/servants", [
            'full_name' => 'Cadastro Proibido',
        ])
        ->assertForbidden();

    expect(Servant::query()->count())->toBe(0);
});

it('denies a route parish different from the authorized request context', function (): void {
    ['user' => $administrator, 'parish' => $parish] = parishManager();
    $externalParish = Parish::query()->create([
        'diocese_id' => $parish->diocese_id,
        'name' => 'Paróquia Externa',
        'timezone' => 'America/Fortaleza',
    ]);

    $this->actingAs($administrator)
        ->withHeader('X-Parish-Id', $parish->id)
        ->getJson("/api/v1/parishes/{$externalParish->id}/servants")
        ->assertForbidden()
        ->assertJsonPath('code', 'PARISH_ACCESS_DENIED');
});

it('keeps the servant history when suspending the pastoral link', function (): void {
    ['user' => $administrator, 'parish' => $parish] = parishManager();
    $person = Person::query()->create(['full_name' => 'Servo Suspenso']);
    $servant = Servant::query()->create([
        'parish_id' => $parish->id,
        'person_id' => $person->id,
        'status' => 'ACTIVE',
        'joined_on' => now()->subMonth()->toDateString(),
        'created_by_user_id' => $administrator->id,
    ]);

    $this->actingAs($administrator)
        ->withHeader('X-Parish-Id', $parish->id)
        ->patchJson("/api/v1/parishes/{$parish->id}/servants/{$servant->id}", [
            'status' => 'SUSPENDED',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'SUSPENDED');

    $servant->refresh();
    expect($servant->status)->toBe('SUSPENDED')
        ->and($servant->left_on)->not->toBeNull();
});

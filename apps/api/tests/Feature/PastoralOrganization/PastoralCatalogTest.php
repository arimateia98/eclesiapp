<?php

declare(strict_types=1);

use App\Modules\EcclesialStructure\Models\Diocese;
use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\ParishUserMembership;
use App\Modules\Identity\Models\ParishUserRole;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\PastoralOrganization\Models\PastoralArea;
use App\Modules\PastoralOrganization\Models\PastoralFunction;
use App\Modules\PastoralOrganization\Models\Servant;
use App\Modules\PastoralOrganization\Models\ServantFunction;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** @return array{user: User, parish: Parish} */
function pastoralCatalogManager(string $roleCode = 'PARISH_ADMIN'): array
{
    $person = Person::query()->create(['full_name' => 'Gestor do CatÃ¡logo']);
    $user = User::query()->create([
        'person_id' => $person->id,
        'login_email' => strtolower($roleCode).'.catalog@example.com',
        'password_hash' => 'senha-segura',
        'status' => 'ACTIVE',
    ]);
    $diocese = Diocese::query()->create([
        'name' => 'Diocese do CatÃ¡logo',
        'timezone' => 'America/Fortaleza',
    ]);
    $parish = Parish::query()->create([
        'diocese_id' => $diocese->id,
        'name' => 'ParÃ³quia do CatÃ¡logo',
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

it('lets an administrator create the pastoral catalog and qualify a servant without a user', function (): void {
    ['user' => $administrator, 'parish' => $parish] = pastoralCatalogManager();
    $servantPerson = Person::query()->create(['full_name' => 'Maria Leitora']);
    $servant = Servant::query()->create([
        'parish_id' => $parish->id,
        'person_id' => $servantPerson->id,
        'status' => 'ACTIVE',
        'created_by_user_id' => $administrator->id,
    ]);

    $areaId = $this->actingAs($administrator)
        ->withHeader('X-Parish-Id', $parish->id)
        ->postJson("/api/v1/parishes/{$parish->id}/pastoral-areas", [
            'code' => 'liturgia',
            'name' => 'Liturgia',
        ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'LITURGIA')
        ->json('data.id');

    $functionId = $this->actingAs($administrator)
        ->withHeader('X-Parish-Id', $parish->id)
        ->postJson("/api/v1/parishes/{$parish->id}/pastoral-functions", [
            'pastoral_area_id' => $areaId,
            'code' => 'leitor_1',
            'name' => 'Leitor 1',
            'assignment_mode' => 'PERSON',
            'requires_qualification' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'LEITOR_1')
        ->assertJsonPath('data.area_name', 'Liturgia')
        ->json('data.id');

    $this->actingAs($administrator)
        ->withHeader('X-Parish-Id', $parish->id)
        ->postJson("/api/v1/parishes/{$parish->id}/servants/{$servant->id}/functions", [
            'pastoral_function_id' => $functionId,
            'notes' => 'FormaÃ§Ã£o concluÃ­da.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'QUALIFIED')
        ->assertJsonPath('data.pastoral_function.name', 'Leitor 1');

    expect($servantPerson->user()->exists())->toBeFalse()
        ->and(ServantFunction::query()->sole()->approved_by_user_id)->toBe($administrator->id);

    $this->actingAs($administrator)
        ->withHeader('X-Parish-Id', $parish->id)
        ->getJson("/api/v1/parishes/{$parish->id}/servants")
        ->assertOk()
        ->assertJsonPath('data.0.functions.0.function_name', 'Leitor 1');
});

it('denies catalog management to a parish viewer', function (): void {
    ['user' => $viewer, 'parish' => $parish] = pastoralCatalogManager('PARISH_VIEWER');

    $this->actingAs($viewer)
        ->withHeader('X-Parish-Id', $parish->id)
        ->postJson("/api/v1/parishes/{$parish->id}/pastoral-areas", [
            'code' => 'MUSICA',
            'name' => 'MÃºsica',
        ])
        ->assertForbidden();

    expect(PastoralArea::query()->count())->toBe(0);
});

it('rejects a function from another parish before qualifying a servant', function (): void {
    ['user' => $administrator, 'parish' => $parish] = pastoralCatalogManager();
    $otherParish = Parish::query()->create([
        'diocese_id' => $parish->diocese_id,
        'name' => 'ParÃ³quia Externa',
        'timezone' => 'America/Fortaleza',
    ]);
    $servantPerson = Person::query()->create(['full_name' => 'Servo Local']);
    $servant = Servant::query()->create([
        'parish_id' => $parish->id,
        'person_id' => $servantPerson->id,
        'status' => 'ACTIVE',
        'created_by_user_id' => $administrator->id,
    ]);
    $area = PastoralArea::query()->create([
        'parish_id' => $otherParish->id,
        'code' => 'ACOLHIDA',
        'name' => 'Acolhida',
        'status' => 'ACTIVE',
        'created_by_user_id' => $administrator->id,
    ]);
    $function = PastoralFunction::query()->create([
        'parish_id' => $otherParish->id,
        'pastoral_area_id' => $area->id,
        'code' => 'ACOLHEDOR',
        'name' => 'Acolhedor',
        'assignment_mode' => 'PERSON',
        'requires_qualification' => true,
        'status' => 'ACTIVE',
        'created_by_user_id' => $administrator->id,
    ]);

    $this->actingAs($administrator)
        ->withHeader('X-Parish-Id', $parish->id)
        ->postJson("/api/v1/parishes/{$parish->id}/servants/{$servant->id}/functions", [
            'pastoral_function_id' => $function->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pastoral_function_id');
});

it('enforces same-parish qualifications in PostgreSQL', function (): void {
    ['user' => $administrator, 'parish' => $parish] = pastoralCatalogManager();
    $otherParish = Parish::query()->create([
        'diocese_id' => $parish->diocese_id,
        'name' => 'Outra ParÃ³quia',
        'timezone' => 'America/Fortaleza',
    ]);
    $person = Person::query()->create(['full_name' => 'Servo Protegido']);
    $servant = Servant::query()->create([
        'parish_id' => $parish->id,
        'person_id' => $person->id,
        'status' => 'ACTIVE',
        'created_by_user_id' => $administrator->id,
    ]);
    $area = PastoralArea::query()->create([
        'parish_id' => $otherParish->id,
        'code' => 'CANTO',
        'name' => 'Canto',
        'status' => 'ACTIVE',
        'created_by_user_id' => $administrator->id,
    ]);
    $function = PastoralFunction::query()->create([
        'parish_id' => $otherParish->id,
        'pastoral_area_id' => $area->id,
        'code' => 'VOCAL',
        'name' => 'Vocal',
        'assignment_mode' => 'PERSON',
        'requires_qualification' => true,
        'status' => 'ACTIVE',
        'created_by_user_id' => $administrator->id,
    ]);

    expect(fn () => ServantFunction::query()->create([
        'parish_id' => $parish->id,
        'servant_id' => $servant->id,
        'pastoral_function_id' => $function->id,
        'status' => 'QUALIFIED',
        'approved_by_user_id' => $administrator->id,
    ]))->toThrow(QueryException::class);
});

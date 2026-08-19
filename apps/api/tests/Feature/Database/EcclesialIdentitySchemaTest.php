<?php

declare(strict_types=1);

use App\Modules\EcclesialStructure\Models\Community;
use App\Modules\EcclesialStructure\Models\Diocese;
use App\Modules\EcclesialStructure\Models\Location;
use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\ParishUserMembership;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps a person independent from a user account', function (): void {
    $person = Person::query()->create([
        'full_name' => 'João Batista',
        'preferred_name' => 'João',
    ]);

    expect($person->user)->toBeNull()
        ->and(User::query()->count())->toBe(0);
});

it('allows one account to have distinct parish memberships', function (): void {
    $diocese = Diocese::query()->create([
        'name' => 'Arquidiocese de Fortaleza',
        'timezone' => 'America/Fortaleza',
    ]);

    $firstParish = Parish::query()->create([
        'diocese_id' => $diocese->id,
        'name' => 'Paróquia São José',
        'timezone' => 'America/Fortaleza',
    ]);

    $secondParish = Parish::query()->create([
        'diocese_id' => $diocese->id,
        'name' => 'Paróquia Nossa Senhora da Paz',
        'timezone' => 'America/Fortaleza',
    ]);

    $person = Person::query()->create(['full_name' => 'Maria de Nazaré']);
    $user = User::query()->create([
        'person_id' => $person->id,
        'login_email' => 'maria@example.com',
        'password_hash' => 'senha-segura',
        'status' => 'ACTIVE',
    ]);

    ParishUserMembership::query()->create([
        'parish_id' => $firstParish->id,
        'user_id' => $user->id,
        'status' => 'ACTIVE',
        'joined_at' => now(),
    ]);

    ParishUserMembership::query()->create([
        'parish_id' => $secondParish->id,
        'user_id' => $user->id,
        'status' => 'ACTIVE',
        'joined_at' => now(),
    ]);

    expect($user->memberships()->count())->toBe(2);
});

it('rejects duplicate user emails without case sensitivity', function (): void {
    $firstPerson = Person::query()->create(['full_name' => 'Pessoa Um']);
    $secondPerson = Person::query()->create(['full_name' => 'Pessoa Dois']);

    User::query()->create([
        'person_id' => $firstPerson->id,
        'login_email' => 'contato@example.com',
    ]);

    expect(fn () => User::query()->create([
        'person_id' => $secondPerson->id,
        'login_email' => 'CONTATO@example.com',
    ]))->toThrow(QueryException::class);
});

it('rejects a location linked to a community from another parish', function (): void {
    $diocese = Diocese::query()->create([
        'name' => 'Diocese de Teste',
        'timezone' => 'America/Fortaleza',
    ]);

    $firstParish = Parish::query()->create([
        'diocese_id' => $diocese->id,
        'name' => 'Primeira Paróquia',
        'timezone' => 'America/Fortaleza',
    ]);

    $secondParish = Parish::query()->create([
        'diocese_id' => $diocese->id,
        'name' => 'Segunda Paróquia',
        'timezone' => 'America/Fortaleza',
    ]);

    $community = Community::query()->create([
        'parish_id' => $firstParish->id,
        'name' => 'Comunidade São Pedro',
    ]);

    expect(fn () => Location::query()->create([
        'parish_id' => $secondParish->id,
        'community_id' => $community->id,
        'name' => 'Capela incompatível',
        'location_type' => 'CHAPEL',
    ]))->toThrow(QueryException::class);
});

it('rejects status values outside the domain catalog', function (): void {
    expect(fn () => Diocese::query()->create([
        'name' => 'Diocese inválida',
        'timezone' => 'America/Fortaleza',
        'status' => 'UNKNOWN',
    ]))->toThrow(QueryException::class);
});

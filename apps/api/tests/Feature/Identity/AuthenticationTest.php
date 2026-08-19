<?php

declare(strict_types=1);

use App\Modules\EcclesialStructure\Models\Diocese;
use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\ParishUserMembership;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\UserExternalIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

function createActiveUser(string $email = 'maria@example.com', string $password = 'senha-segura'): User
{
    $person = Person::query()->create(['full_name' => 'Maria de Nazaré']);

    return User::query()->create([
        'person_id' => $person->id,
        'login_email' => $email,
        'password_hash' => $password,
        'status' => 'ACTIVE',
    ]);
}

it('authenticates an active invited account with local credentials', function (): void {
    $user = createActiveUser();

    $this->postJson('/login', [
        'email' => 'maria@example.com',
        'password' => 'senha-segura',
    ])->assertOk()->assertJsonPath('data.id', $user->id);

    $this->assertAuthenticatedAs($user);
});

it('does not authenticate a blocked account', function (): void {
    $user = createActiveUser();
    $user->forceFill(['status' => 'BLOCKED'])->save();

    $this->postJson('/login', [
        'email' => 'maria@example.com',
        'password' => 'senha-segura',
    ])->assertUnprocessable()->assertJsonPath('code', 'INVALID_CREDENTIALS');

    $this->assertGuest();
});

it('returns only active parish memberships from the current session', function (): void {
    $user = createActiveUser();
    $diocese = Diocese::query()->create(['name' => 'Diocese de Teste', 'timezone' => 'America/Fortaleza']);
    $activeParish = Parish::query()->create(['diocese_id' => $diocese->id, 'name' => 'Paróquia Ativa', 'timezone' => 'America/Fortaleza']);
    $endedParish = Parish::query()->create(['diocese_id' => $diocese->id, 'name' => 'Paróquia Encerrada', 'timezone' => 'America/Fortaleza']);

    ParishUserMembership::query()->create(['parish_id' => $activeParish->id, 'user_id' => $user->id, 'status' => 'ACTIVE']);
    ParishUserMembership::query()->create(['parish_id' => $endedParish->id, 'user_id' => $user->id, 'status' => 'ENDED']);

    $this->actingAs($user)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonCount(1, 'data.parishes')
        ->assertJsonPath('data.parishes.0.id', $activeParish->id);
});

it('rejects the current user endpoint without a session', function (): void {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

it('links a verified Google identity only to a pre-existing active account', function (): void {
    $user = createActiveUser('conta@gmail.com');
    config(['services.google.client_id' => 'client', 'services.google.client_secret' => 'secret']);

    $googleUser = (new SocialiteUser)->map([
        'id' => 'google-subject-123',
        'email' => 'conta@gmail.com',
        'name' => 'Maria',
    ])->setRaw(['verified_email' => true]);

    Socialite::fake('google', $googleUser);

    $this->get('/auth/google/callback')->assertRedirect('http://localhost:3000/?auth=google');
    $this->assertAuthenticatedAs($user);

    expect(UserExternalIdentity::query()->where('provider_subject', 'google-subject-123')->value('user_id'))
        ->toBe($user->id);
});

it('creates an account without parish membership from a new verified Google identity', function (): void {
    config(['services.google.client_id' => 'client', 'services.google.client_secret' => 'secret']);

    $googleUser = (new SocialiteUser)->map([
        'id' => 'unknown-google-subject',
        'email' => 'desconhecido@gmail.com',
        'name' => 'Pessoa sem Paróquia',
    ])->setRaw(['verified_email' => true]);

    Socialite::fake('google', $googleUser);

    $this->get('/auth/google/callback')->assertRedirect('http://localhost:3000/?auth=google');

    $user = User::query()->where('login_email', 'desconhecido@gmail.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->memberships()->count())->toBe(0)
        ->and($user->person?->full_name)->toBe('Pessoa sem Paróquia')
        ->and($user->email_verified_at)->not->toBeNull();
});

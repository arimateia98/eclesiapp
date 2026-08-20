<?php

declare(strict_types=1);

use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(ThrottleRequests::class);
});

it('allows the web application to preflight account registration', function (): void {
    $this->withHeaders([
        'Origin' => 'http://localhost:3000',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'content-type,x-xsrf-token',
    ])->options('/register')
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000')
        ->assertHeader('Access-Control-Allow-Credentials', 'true')
        ->assertHeader('Access-Control-Allow-Methods');
});

it('registers and authenticates an account without parish or servant status', function (): void {
    $response = $this->postJson('/register', [
        'full_name' => 'Ana sem Vínculo',
        'preferred_name' => 'Ana',
        'email' => 'ana@example.com',
        'password' => 'senha-forte-123',
        'password_confirmation' => 'senha-forte-123',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.has_parish_membership', false)
        ->assertJsonPath('data.is_servant', false);

    $user = User::query()->where('login_email', 'ana@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->memberships()->count())->toBe(0)
        ->and($user->person)->toBeInstanceOf(Person::class)
        ->and($user->person?->full_name)->toBe('Ana sem Vínculo');
});

it('does not create duplicate accounts with case variants of the same email', function (): void {
    $this->postJson('/register', [
        'full_name' => 'Primeira Pessoa',
        'email' => 'conta@example.com',
        'password' => 'senha-forte-123',
        'password_confirmation' => 'senha-forte-123',
    ])->assertCreated();

    $this->postJson('/register', [
        'full_name' => 'Segunda Pessoa',
        'email' => 'CONTA@example.com',
        'password' => 'outra-senha-456',
        'password_confirmation' => 'outra-senha-456',
    ])->assertUnprocessable()->assertJsonPath('code', 'REGISTRATION_UNAVAILABLE');

    expect(User::query()->count())->toBe(1)
        ->and(Person::query()->count())->toBe(1);
});

it('validates password confirmation before creating the account', function (): void {
    $this->postJson('/register', [
        'full_name' => 'Cadastro Inválido',
        'email' => 'invalido@example.com',
        'password' => 'senha-forte-123',
        'password_confirmation' => 'senha-diferente',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');

    expect(User::query()->count())->toBe(0)
        ->and(Person::query()->count())->toBe(0);
});

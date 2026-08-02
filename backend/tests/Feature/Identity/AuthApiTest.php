<?php

namespace Tests\Feature\Identity;

use App\Modules\Identity\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

final class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_separate_user_and_person_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Maria',
            'email' => 'MARIA@example.test',
            'password' => 'senha-segura',
            'password_confirmation' => 'senha-segura',
            'full_name' => 'Maria de Nazaré',
            'preferred_name' => 'Maria',
            'device_name' => 'painel-web',
        ])->assertCreated()
            ->assertJsonPath('data.user.email', 'maria@example.test')
            ->assertJsonPath('data.token_type', 'Bearer');

        $token = (string) $response->json('data.token');
        self::assertNotSame('', $token);

        $user = User::query()->where('email', 'maria@example.test')->firstOrFail();
        $this->assertDatabaseHas('people', [
            'user_id' => $user->getKey(),
            'full_name' => 'Maria de Nazaré',
        ]);

        $this->withToken($token)
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Maria de Nazaré');
    }

    public function test_login_rejects_invalid_credentials_and_logout_revokes_current_token(): void
    {
        $user = User::factory()->create([
            'email' => 'gestor@example.test',
            'password' => 'senha-segura',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'senha-incorreta',
            'device_name' => 'painel-web',
        ])->assertUnauthorized()
            ->assertJsonPath('code', 'auth.invalid_credentials');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'senha-segura',
            'device_name' => 'painel-web',
        ])->assertOk();

        $token = (string) $response->json('data.token');

        $this->withToken($token)
            ->deleteJson('/api/v1/auth/token')
            ->assertNoContent();

        Auth::forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/profile')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'auth.unauthenticated');
    }
}

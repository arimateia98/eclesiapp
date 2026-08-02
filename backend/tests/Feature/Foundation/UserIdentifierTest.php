<?php

namespace Tests\Feature\Foundation;

use App\Modules\Identity\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserIdentifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_and_sanctum_tokens_use_ulid_identifiers(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('foundation-test');
        $userId = (string) $user->getKey();

        self::assertSame(26, strlen($userId));
        self::assertNotSame('', $token->plainTextToken);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $userId,
            'tokenable_type' => 'user',
        ]);
    }
}

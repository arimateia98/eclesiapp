<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Actions\RegisterUser;
use App\Modules\Identity\Application\DTOs\RegisterUserData;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Http\Requests\LoginRequest;
use App\Modules\Identity\Http\Requests\RegisterRequest;
use App\Modules\Identity\Http\Resources\UserResource;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

final class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegisterUser $action): JsonResponse
    {
        $user = $action->execute(RegisterUserData::fromArray($request->validated()));
        $token = $user->createToken((string) $request->validated('device_name'))->plainTextToken;

        return $this->tokenResponse($user, $token, Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $email = Str::lower(trim((string) $request->validated('email')));
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check((string) $request->validated('password'), $user->password)) {
            throw new DomainRuleViolation(
                errorCode: 'auth.invalid_credentials',
                message: 'As credenciais informadas são inválidas.',
                httpStatus: 401,
            );
        }

        $token = $user->createToken((string) $request->validated('device_name'))->plainTextToken;

        return $this->tokenResponse($user, $token);
    }

    public function logout(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->noContent();
    }

    private function tokenResponse(User $user, string $token, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'data' => [
                'user' => (new UserResource($user))->resolve(),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], $status);
    }
}

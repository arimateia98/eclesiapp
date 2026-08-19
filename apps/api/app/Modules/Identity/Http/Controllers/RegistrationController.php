<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Actions\RegisterUser;
use App\Modules\Identity\Exceptions\RegistrationFailed;
use App\Modules\Identity\Http\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final class RegistrationController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterUser $registerUser): JsonResponse
    {
        try {
            $user = $registerUser->execute(
                $request->string('full_name')->toString(),
                $request->filled('preferred_name') ? $request->string('preferred_name')->toString() : null,
                $request->string('email')->toString(),
                $request->string('password')->toString(),
            );
        } catch (RegistrationFailed $exception) {
            return response()->json([
                'title' => 'Cadastro indisponível',
                'status' => 422,
                'code' => 'REGISTRATION_UNAVAILABLE',
                'detail' => $exception->getMessage(),
            ], 422);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'has_parish_membership' => false,
                'is_servant' => false,
            ],
            'meta' => ['request_id' => (string) Str::uuid()],
        ], 201);
    }
}

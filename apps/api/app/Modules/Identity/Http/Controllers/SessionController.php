<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Http\Requests\LoginRequest;
use App\Modules\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class SessionController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('login_email', $request->string('email')->toString())->first();

        if (! $user || $user->status !== 'ACTIVE' || ! is_string($user->password_hash) || ! Hash::check($request->string('password')->toString(), $user->password_hash)) {
            return response()->json([
                'title' => 'Credenciais inválidas',
                'status' => 422,
                'code' => 'INVALID_CREDENTIALS',
                'detail' => 'Não foi possível autenticar com as credenciais informadas.',
            ], 422);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json(['data' => ['id' => $user->id], 'meta' => ['request_id' => (string) Str::uuid()]]);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['data' => null, 'meta' => ['request_id' => (string) Str::uuid()]]);
    }
}

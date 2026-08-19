<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LogicException;

final class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['person', 'memberships' => fn ($query) => $query->where('status', 'ACTIVE')->with('parish')]);
        $person = $user->person;

        if (! $person) {
            throw new LogicException('Usuário autenticado sem pessoa vinculada.');
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'email' => $user->login_email,
                'person' => [
                    'id' => $person->id,
                    'full_name' => $person->full_name,
                    'preferred_name' => $person->preferred_name,
                ],
                'parishes' => $user->memberships->map(function ($membership): array {
                    $parish = $membership->parish;

                    if (! $parish) {
                        throw new LogicException('Vínculo paroquial sem paróquia.');
                    }

                    return [
                        'id' => $parish->id,
                        'name' => $parish->name,
                        'timezone' => $parish->timezone,
                    ];
                })->values(),
            ],
            'meta' => ['request_id' => (string) Str::uuid()],
        ]);
    }
}

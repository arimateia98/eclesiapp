<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Actions\AcceptPersonAccountInvitation;
use App\Modules\Identity\Application\DTOs\AcceptPersonAccountInvitationData;
use App\Modules\Identity\Http\Requests\AcceptPersonAccountInvitationRequest;
use App\Modules\Identity\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

final class PersonAccountInvitationAcceptanceController extends Controller
{
    public function store(
        AcceptPersonAccountInvitationRequest $request,
        AcceptPersonAccountInvitation $action,
    ): JsonResponse {
        $user = $action->execute(AcceptPersonAccountInvitationData::fromArray($request->validated()));
        $token = $user->createToken((string) $request->validated('device_name'))->plainTextToken;

        return response()->json([
            'data' => [
                'user' => (new UserResource($user))->resolve(),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }
}

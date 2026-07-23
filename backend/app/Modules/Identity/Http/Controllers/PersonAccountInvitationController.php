<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Actions\CreatePersonAccountInvitation;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Http\Resources\PersonAccountInvitationResource;
use App\Modules\Organizations\Domain\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class PersonAccountInvitationController extends Controller
{
    public function store(
        Request $request,
        Organization $organization,
        string $person,
        CreatePersonAccountInvitation $action,
    ): JsonResponse {
        Gate::authorize('manageMembers', $organization);

        /** @var User $user */
        $user = $request->user();
        $invitation = $action->execute($user, $organization, $person);

        return (new PersonAccountInvitationResource($invitation))->response()->setStatusCode(202);
    }
}

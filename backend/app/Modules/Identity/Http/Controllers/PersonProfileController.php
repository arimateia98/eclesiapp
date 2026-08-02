<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Application\Actions\CreatePersonProfile;
use App\Modules\Identity\Application\DTOs\CreatePersonData;
use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Http\Requests\StorePersonProfileRequest;
use App\Modules\Identity\Http\Resources\PersonResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PersonProfileController extends Controller
{
    public function show(Request $request): PersonResource
    {
        /** @var User $user */
        $user = $request->user();
        $person = Person::query()->where('user_id', $user->getKey())->firstOrFail();

        return new PersonResource($person);
    }

    public function store(
        StorePersonProfileRequest $request,
        CreatePersonProfile $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $person = $action->execute($user, CreatePersonData::fromArray($request->validated()));

        return (new PersonResource($person))->response()->setStatusCode(201);
    }
}

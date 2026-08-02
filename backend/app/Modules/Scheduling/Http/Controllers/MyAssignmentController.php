<?php

namespace App\Modules\Scheduling\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Scheduling\Application\Queries\ListMyAssignments;
use App\Modules\Scheduling\Http\Requests\ListSchedulingResourcesRequest;
use App\Modules\Scheduling\Http\Resources\MyAssignmentResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class MyAssignmentController extends Controller
{
    public function __invoke(ListSchedulingResourcesRequest $request, ListMyAssignments $query): AnonymousResourceCollection
    {
        /** @var User $user */ $user = $request->user();

        return MyAssignmentResource::collection($query->execute($user, (int) $request->validated('per_page', 50)));
    }
}

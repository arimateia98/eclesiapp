<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Exceptions\ParishContextException;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\ActiveParishContext;
use App\Modules\PastoralOrganization\Actions\QualifyServant;
use App\Modules\PastoralOrganization\Http\Requests\StoreServantFunctionRequest;
use App\Modules\PastoralOrganization\Models\PastoralFunction;
use App\Modules\PastoralOrganization\Models\Servant;
use App\Modules\PastoralOrganization\Models\ServantFunction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class ServantFunctionController extends Controller
{
    public function store(
        StoreServantFunctionRequest $request,
        string $parishId,
        string $servantId,
        QualifyServant $qualifyServant,
    ): JsonResponse {
        $context = $this->context($request, $parishId);
        $servant = Servant::query()
            ->where('parish_id', $context->parish->id)
            ->with('parish')
            ->findOrFail($servantId);
        /** @var User $user */
        $user = $request->user();
        Gate::forUser($user)->authorize('update', $servant);

        $functionId = $request->string('pastoral_function_id')->toString();
        $function = PastoralFunction::query()
            ->where('parish_id', $context->parish->id)
            ->findOrFail($functionId);
        /** @var array{status?: string, qualified_on?: string|null, expires_on?: string|null, notes?: string|null} $data */
        $data = $request->safe()->except('pastoral_function_id');
        $qualification = $qualifyServant->execute($servant, $function, $user, $data);
        $qualification->load('pastoralFunction.area');

        return response()->json([
            'data' => $this->serialize($qualification),
            'meta' => ['request_id' => (string) Str::uuid()],
        ], $qualification->wasRecentlyCreated ? 201 : 200);
    }

    private function context(Request $request, string $parishId): ActiveParishContext
    {
        $context = $request->attributes->get(ActiveParishContext::REQUEST_ATTRIBUTE);

        if (! $context instanceof ActiveParishContext || $context->parish->id !== $parishId) {
            throw ParishContextException::accessDenied();
        }

        return $context;
    }

    /** @return array{id: string, status: string, qualified_on: string|null, expires_on: string|null, notes: string|null, pastoral_function: array{id: string, name: string, area_name: string|null}} */
    private function serialize(ServantFunction $qualification): array
    {
        $function = $qualification->pastoralFunction;

        if (! $function) {
            throw new \LogicException('HabilitaÃ§Ã£o sem funÃ§Ã£o pastoral vinculada.');
        }

        return [
            'id' => $qualification->id,
            'status' => $qualification->status,
            'qualified_on' => $qualification->qualified_on?->toDateString(),
            'expires_on' => $qualification->expires_on?->toDateString(),
            'notes' => $qualification->notes,
            'pastoral_function' => [
                'id' => $function->id,
                'name' => $function->name,
                'area_name' => $function->area?->name,
            ],
        ];
    }
}

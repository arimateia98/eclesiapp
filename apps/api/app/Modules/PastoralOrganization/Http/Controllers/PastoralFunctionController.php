<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Exceptions\ParishContextException;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\ActiveParishContext;
use App\Modules\PastoralOrganization\Http\Requests\StorePastoralFunctionRequest;
use App\Modules\PastoralOrganization\Models\PastoralFunction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class PastoralFunctionController extends Controller
{
    public function index(Request $request, string $parishId): JsonResponse
    {
        $context = $this->context($request, $parishId);
        Gate::forUser($request->user())->authorize('viewAny', [PastoralFunction::class, $context->parish]);

        $functions = PastoralFunction::query()
            ->where('parish_id', $context->parish->id)
            ->with('area')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $functions->map(fn (PastoralFunction $function): array => $this->serialize($function)),
            'meta' => ['request_id' => (string) Str::uuid()],
        ]);
    }

    public function store(StorePastoralFunctionRequest $request, string $parishId): JsonResponse
    {
        $context = $this->context($request, $parishId);
        /** @var User $user */
        $user = $request->user();
        Gate::forUser($user)->authorize('create', [PastoralFunction::class, $context->parish]);

        /** @var array{pastoral_area_id: string, code: string, name: string, assignment_mode: string, requires_qualification: bool} $data */
        $data = $request->validated();
        $function = PastoralFunction::query()->create([
            ...$data,
            'parish_id' => $context->parish->id,
            'status' => 'ACTIVE',
            'created_by_user_id' => $user->id,
        ]);
        $function->load('area');

        return response()->json([
            'data' => $this->serialize($function),
            'meta' => ['request_id' => (string) Str::uuid()],
        ], 201);
    }

    private function context(Request $request, string $parishId): ActiveParishContext
    {
        $context = $request->attributes->get(ActiveParishContext::REQUEST_ATTRIBUTE);

        if (! $context instanceof ActiveParishContext || $context->parish->id !== $parishId) {
            throw ParishContextException::accessDenied();
        }

        return $context;
    }

    /** @return array{id: string, parish_id: string, pastoral_area_id: string, area_name: string|null, code: string, name: string, assignment_mode: string, requires_qualification: bool, status: string} */
    private function serialize(PastoralFunction $function): array
    {
        return [
            'id' => $function->id,
            'parish_id' => $function->parish_id,
            'pastoral_area_id' => $function->pastoral_area_id,
            'area_name' => $function->area?->name,
            'code' => $function->code,
            'name' => $function->name,
            'assignment_mode' => $function->assignment_mode,
            'requires_qualification' => (bool) $function->requires_qualification,
            'status' => $function->status,
        ];
    }
}

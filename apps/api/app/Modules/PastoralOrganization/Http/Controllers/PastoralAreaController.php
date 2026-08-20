<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Exceptions\ParishContextException;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\ActiveParishContext;
use App\Modules\PastoralOrganization\Http\Requests\StorePastoralAreaRequest;
use App\Modules\PastoralOrganization\Models\PastoralArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class PastoralAreaController extends Controller
{
    public function index(Request $request, string $parishId): JsonResponse
    {
        $context = $this->context($request, $parishId);
        Gate::forUser($request->user())->authorize('viewAny', [PastoralArea::class, $context->parish]);

        $areas = PastoralArea::query()
            ->where('parish_id', $context->parish->id)
            ->withCount('functions')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $areas->map(fn (PastoralArea $area): array => $this->serialize($area)),
            'meta' => ['request_id' => (string) Str::uuid()],
        ]);
    }

    public function store(StorePastoralAreaRequest $request, string $parishId): JsonResponse
    {
        $context = $this->context($request, $parishId);
        /** @var User $user */
        $user = $request->user();
        Gate::forUser($user)->authorize('create', [PastoralArea::class, $context->parish]);

        /** @var array{code: string, name: string, description?: string|null} $data */
        $data = $request->validated();
        $area = PastoralArea::query()->create([
            ...$data,
            'parish_id' => $context->parish->id,
            'status' => 'ACTIVE',
            'created_by_user_id' => $user->id,
        ]);

        return response()->json([
            'data' => $this->serialize($area),
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

    /** @return array{id: string, parish_id: string, code: string, name: string, description: string|null, status: string, functions_count: int} */
    private function serialize(PastoralArea $area): array
    {
        return [
            'id' => $area->id,
            'parish_id' => $area->parish_id,
            'code' => $area->code,
            'name' => $area->name,
            'description' => $area->description,
            'status' => $area->status,
            'functions_count' => (int) ($area->functions_count ?? 0),
        ];
    }
}

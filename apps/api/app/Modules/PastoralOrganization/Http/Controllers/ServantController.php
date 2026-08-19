<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Exceptions\ParishContextException;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\ActiveParishContext;
use App\Modules\PastoralOrganization\Actions\CreateServant;
use App\Modules\PastoralOrganization\Http\Requests\StoreServantRequest;
use App\Modules\PastoralOrganization\Http\Requests\UpdateServantRequest;
use App\Modules\PastoralOrganization\Models\Servant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class ServantController extends Controller
{
    public function index(Request $request, string $parishId): JsonResponse
    {
        $context = $this->context($request, $parishId);
        Gate::forUser($request->user())->authorize('viewAny', [Servant::class, $context->parish]);
        $search = trim((string) $request->query('search', ''));

        $servants = Servant::query()
            ->where('parish_id', $context->parish->id)
            ->with('person')
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('person', fn ($personQuery) => $personQuery->where('full_name', 'ILIKE', '%'.$search.'%'));
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => collect($servants->items())->map(fn (Servant $servant): array => $this->serialize($servant)),
            'meta' => [
                'request_id' => (string) Str::uuid(),
                'current_page' => $servants->currentPage(),
                'last_page' => $servants->lastPage(),
                'total' => $servants->total(),
            ],
        ]);
    }

    public function store(StoreServantRequest $request, string $parishId, CreateServant $createServant): JsonResponse
    {
        $context = $this->context($request, $parishId);
        /** @var User $user */
        $user = $request->user();
        Gate::forUser($user)->authorize('create', [Servant::class, $context->parish]);

        /** @var array{full_name: string, preferred_name?: string|null, phone?: string|null, email?: string|null, joined_on?: string|null} $data */
        $data = $request->validated();
        $servant = $createServant->execute($context->parish, $user, $data);

        return response()->json([
            'data' => $this->serialize($servant),
            'meta' => ['request_id' => (string) Str::uuid()],
        ], 201);
    }

    public function update(UpdateServantRequest $request, string $parishId, string $servantId): JsonResponse
    {
        $context = $this->context($request, $parishId);
        $servant = Servant::query()
            ->where('parish_id', $context->parish->id)
            ->with(['parish', 'person'])
            ->findOrFail($servantId);
        Gate::forUser($request->user())->authorize('update', $servant);
        $status = $request->string('status')->toString();
        $leftOn = $request->filled('left_on') ? $request->string('left_on')->toString() : null;

        $servant->forceFill([
            'status' => $status,
            'left_on' => $status === 'ACTIVE' ? null : ($leftOn ?? now()->toDateString()),
        ])->save();

        return response()->json([
            'data' => $this->serialize($servant),
            'meta' => ['request_id' => (string) Str::uuid()],
        ]);
    }

    private function context(Request $request, string $parishId): ActiveParishContext
    {
        $context = $request->attributes->get(ActiveParishContext::REQUEST_ATTRIBUTE);

        if (! $context instanceof ActiveParishContext || $context->parish->id !== $parishId) {
            throw ParishContextException::accessDenied();
        }

        return $context;
    }

    /** @return array{id: string, parish_id: string, person: array{id: string, full_name: string, preferred_name: string|null, phone: string|null, email: string|null}, status: string, joined_on: string|null, left_on: string|null, has_user: bool} */
    private function serialize(Servant $servant): array
    {
        $person = $servant->person;

        if (! $person) {
            throw new \LogicException('Servo sem pessoa vinculada.');
        }

        return [
            'id' => $servant->id,
            'parish_id' => $servant->parish_id,
            'person' => [
                'id' => $person->id,
                'full_name' => $person->full_name,
                'preferred_name' => $person->preferred_name,
                'phone' => $person->phone,
                'email' => $person->email,
            ],
            'status' => $servant->status,
            'joined_on' => $servant->joined_on?->toDateString(),
            'left_on' => $servant->left_on?->toDateString(),
            'has_user' => $person->user()->exists(),
        ];
    }
}

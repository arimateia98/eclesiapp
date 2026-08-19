<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Exceptions\ParishContextException;
use App\Modules\Identity\Http\Requests\SelectActiveParishRequest;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\ResolveUserParishContext;
use App\Modules\Identity\Support\ActiveParishContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class ActiveParishController extends Controller
{
    public function store(SelectActiveParishRequest $request, ResolveUserParishContext $resolver): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $parishId = $request->string('parish_id')->toString();
        $parish = Parish::query()->find($parishId);

        if (! $parish || Gate::forUser($user)->denies('view', $parish)) {
            throw ParishContextException::accessDenied();
        }

        $context = $resolver->resolve($user, $parishId);
        $request->session()->put(ActiveParishContext::SESSION_KEY, $parishId);

        return $this->response($context);
    }

    public function show(Request $request): JsonResponse
    {
        $context = $request->attributes->get(ActiveParishContext::REQUEST_ATTRIBUTE);

        if (! $context instanceof ActiveParishContext) {
            throw ParishContextException::required();
        }

        return $this->response($context);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->session()->forget(ActiveParishContext::SESSION_KEY);

        return response()->json([
            'data' => null,
            'meta' => ['request_id' => (string) Str::uuid()],
        ]);
    }

    private function response(ActiveParishContext $context): JsonResponse
    {
        return response()->json([
            'data' => $context->toArray(),
            'meta' => ['request_id' => (string) Str::uuid()],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\ResolveUserParishContext;
use App\Modules\Identity\Support\ActiveParishContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class ResolveActiveParish
{
    public function __construct(private ResolveUserParishContext $resolver) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $requestedParishId = $request->header('X-Parish-Id');

        if (! is_string($requestedParishId) || $requestedParishId === '') {
            $requestedParishId = $request->hasSession()
                ? $request->session()->get(ActiveParishContext::SESSION_KEY)
                : null;
        }

        $context = $this->resolver->resolve(
            $user,
            is_string($requestedParishId) ? $requestedParishId : null,
        );
        $request->attributes->set(ActiveParishContext::REQUEST_ATTRIBUTE, $context);

        return $next($request);
    }
}

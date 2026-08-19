<?php

use App\Modules\Identity\Exceptions\ParishContextException;
use App\Modules\Identity\Http\Middleware\ResolveActiveParish;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->alias([
            'active.parish' => ResolveActiveParish::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ParishContextException $exception, Request $request) {
            $requestId = (string) Str::uuid();

            return response()->json([
                'title' => $exception->getMessage(),
                'status' => $exception->httpStatus,
                'code' => $exception->errorCode,
                'detail' => $exception->getMessage(),
                'meta' => ['request_id' => $requestId],
            ], $exception->httpStatus)->header('X-Request-Id', $requestId);
        });
    })->create();

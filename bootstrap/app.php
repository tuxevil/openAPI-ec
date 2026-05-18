<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'internal.auth' => \App\Http\Middleware\AuthenticateInternalSystem::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\ProviderException $exception, $request) {
            return response()->json([
                'code' => $exception->apiCode(),
                'message' => $exception->getMessage(),
                'details' => $exception->details(),
                'provider' => $exception->provider(),
            ], $exception->httpStatus());
        });
    })->create();

<?php

use App\Exceptions\ProviderException;
use App\Http\Middleware\AuthenticateInternalSystem;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'internal.auth' => AuthenticateInternalSystem::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ProviderException $exception, $request) {
            $details = $exception->details();
            $debug = config('app.debug') === true;

            $safeDetails = array_filter([
                'status' => $details['status'] ?? null,
                'body' => $debug ? ($details['body'] ?? null) : null,
                'exception' => $debug ? ($details['exception'] ?? null) : null,
            ], fn ($value) => $value !== null);

            return response()->json([
                'code' => $exception->apiCode(),
                'message' => $exception->getMessage(),
                'details' => $safeDetails,
                'provider' => $exception->provider(),
            ], $exception->httpStatus());
        });

        $exceptions->render(function (Throwable $exception, $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException
                || $exception instanceof AuthenticationException
                || $exception instanceof HttpExceptionInterface
                || $exception instanceof HttpResponseException) {
                return null;
            }

            return response()->json([
                'code' => 'internal_error',
                'message' => $exception->getMessage(),
                'details' => [],
                'provider' => null,
            ], 500);
        });
    })->create();

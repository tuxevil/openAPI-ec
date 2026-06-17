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
        // ProviderException es un error de negocio esperado (timeout del
        // proveedor, 4xx/5xx aguas arriba, credenciales invalidas). El
        // render handler de abajo ya devuelve toda la informacion relevante
        // al cliente, asi que no necesitamos que el reporter por defecto
        // intente loguearlo: si el disco de logs esta lleno o el archivo
        // no es escribible, queremos que la API responda con el 504/502
        // correspondiente, no que escupa un 500 por una excepcion de Monolog.
        $exceptions->dontReport([
            ProviderException::class,
        ]);

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

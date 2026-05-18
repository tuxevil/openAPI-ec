<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateInternalSystem
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return $this->unauthorized('Missing bearer token.');
        }

        $system = $this->resolveSystem($token);

        if ($system === null) {
            return $this->unauthorized('Invalid bearer token.');
        }

        $request->attributes->set('internalSystem', $system);
        Log::withContext(['internal_system' => $system]);

        return $next($request);
    }

    protected function resolveSystem(string $token): ?string
    {
        $systems = config('internal_systems.tokens', []);

        foreach ($systems as $system => $configuredToken) {
            if (is_string($configuredToken) && hash_equals($configuredToken, $token)) {
                return $system;
            }
        }

        return null;
    }

    protected function unauthorized(string $message): JsonResponse
    {
        return response()->json([
            'code' => 'invalid_internal_token',
            'message' => $message,
            'details' => [],
            'provider' => null,
        ], 401);
    }
}

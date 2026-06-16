<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $system = (string) $request->attributes->get('internalSystem', $request->ip() ?? 'anonymous');

            return Limit::perMinute(60)->by('api:'.$system)->response(function (): JsonResponse {
                return new JsonResponse([
                    'code' => 'rate_limited',
                    'message' => 'Demasiadas solicitudes. Intenta de nuevo en un minuto.',
                    'details' => [],
                    'provider' => null,
                ], 429);
            });
        });
    }
}

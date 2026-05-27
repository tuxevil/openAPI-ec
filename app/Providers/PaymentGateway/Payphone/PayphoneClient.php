<?php

namespace App\Providers\PaymentGateway\Payphone;

use App\Exceptions\ProviderException;
use App\ValueObjects\GatewayCredentials;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PayphoneClient
{
    public function __construct(protected GatewayCredentials $credentials) {}

    public function get(string $path): array
    {
        return $this->request('get', $path);
    }

    public function post(string $path, array $body = []): array
    {
        return $this->request('post', $path, $body);
    }

    protected function request(string $method, string $path, ?array $body = null): array
    {
        try {
            $response = Http::baseUrl(config('services.payphone.base_url'))
                ->acceptJson()
                ->contentType('application/json')
                ->timeout((int) config('services.payphone.timeout'))
                ->withToken($this->credentials->bearerToken)
                ->send($method, $path, array_filter([
                    'json' => $body,
                ]));
        } catch (ConnectionException $exception) {
            throw new ProviderException(
                message: 'Payphone request timed out.',
                httpStatus: 504,
                apiCode: 'provider_timeout',
                details: ['exception' => $exception->getMessage()],
                provider: 'payphone',
            );
        }

        if ($response->failed()) {
            throw new ProviderException(
                message: 'Payphone request failed.',
                httpStatus: $response->status() >= 500 ? 502 : 422,
                apiCode: $response->status() >= 500 ? 'provider_upstream_error' : 'provider_request_error',
                details: [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ],
                provider: 'payphone',
            );
        }

        return $response->json() ?? [];
    }
}

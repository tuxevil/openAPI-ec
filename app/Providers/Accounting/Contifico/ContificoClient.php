<?php

namespace App\Providers\Accounting\Contifico;

use App\Exceptions\ProviderException;
use App\ValueObjects\ProviderCredentials;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ContificoClient
{
    public function __construct(protected ProviderCredentials $credentials) {}

    public function get(string $path, array $query = []): array
    {
        return $this->request('get', $path, $query);
    }

    public function post(string $path, array $body = [], array $query = []): array
    {
        return $this->request('post', $path, $query, $body);
    }

    public function put(string $path, array $body = [], array $query = []): array
    {
        return $this->request('put', $path, $query, $body);
    }

    protected function request(string $method, string $path, array $query = [], ?array $body = null): array
    {
        try {
            $response = Http::baseUrl(config('services.contifico.base_url'))
                ->acceptJson()
                ->contentType('application/json')
                ->timeout((int) config('services.contifico.timeout'))
                ->withHeaders([
                    'Authorization' => $this->credentials->apiKey,
                ])
                ->send($method, $path, array_filter([
                    'query' => $query,
                    'json' => $body,
                ]));
        } catch (ConnectionException $exception) {
            throw new ProviderException(
                message: 'Contifico request timed out.',
                httpStatus: 504,
                apiCode: 'provider_timeout',
                details: ['exception' => $exception->getMessage()],
                provider: 'contifico',
            );
        }

        if ($response->failed()) {
            throw new ProviderException(
                message: 'Contifico request failed.',
                httpStatus: $response->status() >= 500 ? 502 : 422,
                apiCode: $response->status() >= 500 ? 'provider_upstream_error' : 'provider_request_error',
                details: [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ],
                provider: 'contifico',
            );
        }

        return $response->json() ?? [];
    }

    public function posQuery(): array
    {
        return $this->credentials->posToken ? ['pos' => $this->credentials->posToken] : [];
    }
}

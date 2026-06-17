<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProviderErrorMappingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'internal_systems.tokens' => ['nexOS' => 'secret-token'],
            'services.contifico.base_url' => 'https://api.contifico.test',
            'services.contifico.timeout' => 30,
            'services.payphone.base_url' => 'https://api.payphone.test',
            'services.payphone.timeout' => 30,
        ]);
    }

    public function test_contifico_5xx_maps_to_502_provider_upstream_error(): void
    {
        Http::fake([
            'api.contifico.test/*' => Http::response(['error' => 'boom'], 503),
        ]);

        $response = $this->authorizedProvider()->getJson('/api/v1/contacts?provider=contifico');

        $response->assertStatus(502)
            ->assertJsonPath('code', 'provider_upstream_error')
            ->assertJsonPath('provider', 'contifico');
    }

    public function test_contifico_4xx_maps_to_422_provider_request_error(): void
    {
        Http::fake([
            'api.contifico.test/*' => Http::response(['error' => 'bad'], 400),
        ]);

        $response = $this->authorizedProvider()->getJson('/api/v1/contacts?provider=contifico');

        $response->assertStatus(422)
            ->assertJsonPath('code', 'provider_request_error');
    }

    public function test_payphone_4xx_maps_to_422_provider_request_error(): void
    {
        Http::fake([
            'api.payphone.test/*' => Http::response(['error' => 'bad'], 401),
        ]);

        $response = $this->authorizedGateway()->getJson('/api/v1/payment-gateways/transactions/1?provider=payphone');

        $response->assertStatus(422)
            ->assertJsonPath('code', 'provider_request_error')
            ->assertJsonPath('provider', 'payphone');
    }

    public function test_api_returns_json_even_when_log_file_is_not_writable(): void
    {
        // Usamos un archivo en sys_get_temp_dir que el runner del test
        // siempre es dueno y puede chmodear, en lugar de storage/logs/laravel.log
        // que puede pertenecer a otro usuario (ej. www-data en Docker local).
        $logFile = tempnam(sys_get_temp_dir(), 'openapi-ec-unwritable-');
        file_put_contents($logFile, '');

        // Reconfiguramos el canal de log a este archivo y forzamos
        // re-resolucion del LogManager para que tome la nueva ruta.
        config([
            'logging.default' => 'single',
            'logging.channels.single.path' => $logFile,
        ]);
        $this->app->forgetInstance(LogManager::class);
        Log::clearResolvedInstances();

        // Ahora hacemos el archivo no escribible.
        chmod($logFile, 0444);

        try {
            Http::fake(function () {
                throw new ConnectionException('timeout');
            });

            $response = $this->authorizedProvider()->getJson('/api/v1/contacts?provider=contifico');

            $response->assertStatus(504)
                ->assertJsonPath('code', 'provider_timeout')
                ->assertJsonPath('provider', 'contifico');
        } finally {
            chmod($logFile, 0644);
            @unlink($logFile);
        }
    }

    public function test_unhandled_api_exception_still_returns_json(): void
    {
        config(['services.contifico.base_url' => null]);

        $response = $this->authorizedProvider()->getJson('/api/v1/contacts?provider=contifico');

        $response->assertStatus(500)
            ->assertJsonPath('code', 'internal_error')
            ->assertJsonPath('provider', null);
    }

    public function test_provider_error_body_is_hidden_when_debug_is_disabled(): void
    {
        config(['app.debug' => false]);

        Http::fake([
            'api.contifico.test/*' => Http::response(['secret' => 'token-xyz'], 503),
        ]);

        $response = $this->authorizedProvider()->getJson('/api/v1/contacts?provider=contifico');

        $response->assertStatus(502)
            ->assertJsonPath('code', 'provider_upstream_error')
            ->assertJsonPath('details.status', 503);

        $body = $response->json('details.body');
        $this->assertNull($body, 'details.body must not leak in non-debug mode.');
    }

    public function test_provider_error_body_is_visible_when_debug_is_enabled(): void
    {
        config(['app.debug' => true]);

        Http::fake([
            'api.contifico.test/*' => Http::response(['detail' => 'upstream said no'], 503),
        ]);

        $response = $this->authorizedProvider()->getJson('/api/v1/contacts?provider=contifico');

        $response->assertStatus(502)
            ->assertJsonPath('code', 'provider_upstream_error')
            ->assertJsonPath('details.status', 503)
            ->assertJsonPath('details.body.detail', 'upstream said no');
    }

    protected function authorized(): self
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer secret-token',
            'Accept' => 'application/json',
        ]);
    }

    protected function authorizedProvider(): self
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer secret-token',
            'Accept' => 'application/json',
            'X-Provider-Api-Key' => 'provider-key',
            'X-Provider-Pos-Token' => 'pos-token',
        ]);
    }

    protected function authorizedGateway(): self
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer secret-token',
            'Accept' => 'application/json',
            'X-Gateway-Bearer' => 'gateway-token',
        ]);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContificoApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'internal_systems.tokens' => ['nexOS' => 'secret-token'],
            'services.contifico.base_url' => 'https://api.contifico.test',
            'services.contifico.timeout' => 30,
        ]);
    }

    public function test_it_requires_provider_api_key_for_get_requests(): void
    {
        $response = $this->authorized()->getJson('/api/v1/contacts?provider=contifico');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['credentials.apiKey']);
    }

    public function test_it_requires_pos_token_for_contact_writes(): void
    {
        $response = $this->authorized()->postJson('/api/v1/contacts', [
            'provider' => 'contifico',
            'credentials' => ['apiKey' => 'provider-key'],
            'data' => [
                'name' => 'Cliente Demo',
                'identification' => ['type' => 'CEDULA', 'value' => '0912345678'],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['credentials.posToken']);
    }

    public function test_it_lists_contacts_with_normalized_response(): void
    {
        Http::fake([
            'https://api.contifico.test/api/v2/persona/*' => Http::response([
                [
                    'id' => 'C1',
                    'razon_social' => 'Cliente Uno',
                    'cedula' => '0912345678',
                    'email' => 'cliente@example.com',
                    'es_cliente' => true,
                    'es_proveedor' => false,
                    'estado' => 'A',
                ],
            ], 200),
        ]);

        $response = $this->authorizedProvider()->getJson('/api/v1/contacts?provider=contifico');

        $response->assertOk()
            ->assertJsonPath('provider', 'contifico')
            ->assertJsonPath('operation', 'contacts.list')
            ->assertJsonPath('data.items.0.externalId', 'C1')
            ->assertJsonPath('data.items.0.identification.type', 'CEDULA');
    }

    public function test_it_creates_an_invoice_with_computed_contifico_payload(): void
    {
        Http::fake(function (Request $request) {
            if ($request->url() === 'https://api.contifico.test/api/v2/documento/') {
                $payload = $request->data();

                $this->assertSame('pos-token', $payload['pos']);
                $this->assertSame('001-001-000000123', $payload['documento']);
                $this->assertSame(100.0, $payload['subtotal_12']);
                $this->assertSame(15.0, $payload['iva']);
                $this->assertSame(115.0, $payload['total']);

                return Http::response([
                    'id' => 'INV-1',
                    'documento' => '001-001-000000123',
                    'fecha_emision' => '18/05/2026',
                    'cliente' => ['razon_social' => 'Cliente Uno', 'cedula' => '0912345678'],
                    'detalles' => $payload['detalles'],
                    'subtotal_0' => 0,
                    'subtotal_12' => 100,
                    'iva' => 15,
                    'total' => 115,
                    'estado' => 'P',
                ], 200);
            }

            return Http::response([], 404);
        });

        $response = $this->authorized()->postJson('/api/v1/invoices', [
            'provider' => 'contifico',
            'credentials' => ['apiKey' => 'provider-key', 'posToken' => 'pos-token'],
            'data' => [
                'number' => '001-001-000000123',
                'issuedAt' => '2026-05-18',
                'customer' => [
                    'name' => 'Cliente Uno',
                    'identification' => ['type' => 'CEDULA', 'value' => '0912345678'],
                ],
                'items' => [
                    [
                        'productExternalId' => 'PROD-1',
                        'quantity' => 2,
                        'unitPrice' => 50,
                    ],
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('externalId', 'INV-1')
            ->assertJsonPath('data.total', 115);
    }

    public function test_it_maps_upstream_timeouts(): void
    {
        Http::fake(function () {
            throw new ConnectionException('timeout');
        });

        $response = $this->authorizedProvider()->getJson('/api/v1/contacts?provider=contifico');

        $response->assertStatus(504)
            ->assertJsonPath('code', 'provider_timeout');
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
}

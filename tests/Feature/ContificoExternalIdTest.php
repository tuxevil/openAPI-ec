<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContificoExternalIdTest extends TestCase
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

    public function test_create_contact_without_id_returns_null_external_id(): void
    {
        Http::fake([
            'api.contifico.test/api/v2/persona/*' => Http::response([
                'razon_social' => 'Cliente Demo',
                'cedula' => '0912345678',
            ], 200),
        ]);

        $response = $this->authorized()->postJson('/api/v1/contacts', [
            'provider' => 'contifico',
            'credentials' => ['apiKey' => 'k', 'posToken' => 'pos'],
            'data' => [
                'name' => 'Cliente Demo',
                'identification' => ['type' => 'CEDULA', 'value' => '0912345678'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('externalId', null);
    }

    public function test_create_invoice_without_id_returns_null_external_id(): void
    {
        Http::fake([
            'api.contifico.test/api/v2/documento/' => Http::response([
                'documento' => '001-001-000000123',
                'cliente' => ['razon_social' => 'C', 'cedula' => '0912345678'],
                'detalles' => [],
                'subtotal_0' => 0,
                'subtotal_12' => 100,
                'iva' => 15,
                'total' => 115,
            ], 200),
        ]);

        $response = $this->authorized()->postJson('/api/v1/invoices', [
            'provider' => 'contifico',
            'credentials' => ['apiKey' => 'k', 'posToken' => 'pos'],
            'data' => [
                'number' => '001-001-000000123',
                'issuedAt' => '2026-05-18',
                'customer' => [
                    'name' => 'Cliente Demo',
                    'identification' => ['type' => 'CEDULA', 'value' => '0912345678'],
                ],
                'items' => [
                    ['productExternalId' => 'P1', 'quantity' => 1, 'unitPrice' => 100],
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('externalId', null);
    }

    public function test_get_contact_falls_back_to_path_id_when_response_lacks_id(): void
    {
        Http::fake([
            'api.contifico.test/api/v2/persona/EXT-1/*' => Http::response([
                'razon_social' => 'Cliente Demo',
            ], 200),
        ]);

        $response = $this->authorizedProvider()->getJson('/api/v1/contacts/EXT-1?provider=contifico');

        $response->assertOk()
            ->assertJsonPath('externalId', 'EXT-1');
    }

    public function test_get_contact_prefers_response_id_over_path(): void
    {
        Http::fake([
            'api.contifico.test/api/v2/persona/EXT-1/*' => Http::response([
                'id' => 'C-99',
                'razon_social' => 'Cliente Demo',
            ], 200),
        ]);

        $response = $this->authorizedProvider()->getJson('/api/v1/contacts/EXT-1?provider=contifico');

        $response->assertOk()
            ->assertJsonPath('externalId', 'C-99');
    }

    public function test_create_uses_id_integracion_when_id_missing(): void
    {
        Http::fake([
            'api.contifico.test/api/v2/persona/*' => Http::response([
                'id_integracion' => 'INT-77',
                'razon_social' => 'Cliente Demo',
            ], 200),
        ]);

        $response = $this->authorized()->postJson('/api/v1/contacts', [
            'provider' => 'contifico',
            'credentials' => ['apiKey' => 'k', 'posToken' => 'pos'],
            'data' => [
                'name' => 'Cliente Demo',
                'identification' => ['type' => 'CEDULA', 'value' => '0912345678'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('externalId', 'INT-77');
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
        ]);
    }
}

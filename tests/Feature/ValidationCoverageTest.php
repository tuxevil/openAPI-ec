<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ValidationCoverageTest extends TestCase
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

    public function test_contact_identification_consumidor_final_requires_exact_value(): void
    {
        $response = $this->authorized()->postJson('/api/v1/contacts', [
            'provider' => 'contifico',
            'credentials' => ['apiKey' => 'k', 'posToken' => 'pos'],
            'data' => [
                'name' => 'Consumidor',
                'identification' => ['type' => 'CONSUMIDOR_FINAL', 'value' => '1234567890'],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data.identification.value']);
    }

    public function test_contact_identification_consumidor_final_with_correct_value_passes(): void
    {
        Http::fake([
            'api.contifico.test/api/v2/persona/*' => Http::response([
                'id' => 'CF-1',
                'razon_social' => 'Consumidor Final',
                'ruc' => '9999999999999',
            ], 200),
        ]);

        $response = $this->authorized()->postJson('/api/v1/contacts', [
            'provider' => 'contifico',
            'credentials' => ['apiKey' => 'k', 'posToken' => 'pos'],
            'data' => [
                'name' => 'Consumidor',
                'identification' => ['type' => 'CONSUMIDOR_FINAL', 'value' => '9999999999999'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('externalId', 'CF-1');
    }

    public function test_ruc_identification_must_be_13_digits(): void
    {
        $response = $this->authorized()->postJson('/api/v1/contacts', [
            'provider' => 'contifico',
            'credentials' => ['apiKey' => 'k', 'posToken' => 'pos'],
            'data' => [
                'name' => 'Empresa',
                'identification' => ['type' => 'RUC', 'value' => '123'],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data.identification.value']);
    }

    public function test_pasaporte_identification_must_match_pattern(): void
    {
        $response = $this->authorized()->postJson('/api/v1/contacts', [
            'provider' => 'contifico',
            'credentials' => ['apiKey' => 'k', 'posToken' => 'pos'],
            'data' => [
                'name' => 'Extranjero',
                'identification' => ['type' => 'PASAPORTE', 'value' => 'AB'],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data.identification.value']);
    }

    public function test_product_create_does_not_require_pos_token(): void
    {
        Http::fake([
            'api.contifico.test/api/v2/producto/' => Http::response([
                'id' => 'P-1',
                'nombre' => 'Prod',
                'codigo' => 'P-1',
            ], 200),
        ]);

        $response = $this->authorized()->postJson('/api/v1/products', [
            'provider' => 'contifico',
            'credentials' => ['apiKey' => 'k'],
            'data' => [
                'code' => 'P-1',
                'name' => 'Producto Demo',
                'type' => 'PRO',
                'price' => 10.5,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('externalId', 'P-1');
    }

    public function test_product_create_requires_valid_type(): void
    {
        $response = $this->authorized()->postJson('/api/v1/products', [
            'provider' => 'contifico',
            'credentials' => ['apiKey' => 'k'],
            'data' => [
                'code' => 'P-1',
                'name' => 'Producto Demo',
                'type' => 'INVALID',
                'price' => 10.5,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data.type']);
    }

    public function test_invoice_items_require_minimum_one(): void
    {
        $response = $this->authorized()->postJson('/api/v1/invoices', [
            'provider' => 'contifico',
            'credentials' => ['apiKey' => 'k', 'posToken' => 'pos'],
            'data' => [
                'number' => '001-001-000000001',
                'issuedAt' => '2026-05-18',
                'customer' => [
                    'name' => 'C',
                    'identification' => ['type' => 'CEDULA', 'value' => '0912345678'],
                ],
                'items' => [],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data.items']);
    }

    public function test_invoice_item_quantity_must_be_positive(): void
    {
        $response = $this->authorized()->postJson('/api/v1/invoices', [
            'provider' => 'contifico',
            'credentials' => ['apiKey' => 'k', 'posToken' => 'pos'],
            'data' => [
                'number' => '001-001-000000001',
                'issuedAt' => '2026-05-18',
                'customer' => [
                    'name' => 'C',
                    'identification' => ['type' => 'CEDULA', 'value' => '0912345678'],
                ],
                'items' => [
                    ['productExternalId' => 'P1', 'quantity' => 0, 'unitPrice' => 10],
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data.items.0.quantity']);
    }

    public function test_payphone_reversal_requires_transaction_id(): void
    {
        $response = $this->authorized()->postJson('/api/v1/payment-gateways/reversals', [
            'provider' => 'payphone',
            'credentials' => ['bearerToken' => 'gw'],
            'data' => [
                'clientTransactionId' => 'SYS-1',
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data.transactionId']);
    }

    public function test_payphone_sale_amounts_must_balance(): void
    {
        $response = $this->authorized()->postJson('/api/v1/payment-gateways/sales', [
            'provider' => 'payphone',
            'credentials' => ['bearerToken' => 'gw'],
            'data' => [
                'phoneNumber' => '0999999999',
                'countryCode' => '593',
                'reference' => 'r',
                'clientTransactionId' => 'c',
                'amount' => 200,
                'amountWithTax' => 100,
                'amountWithoutTax' => 0,
                'tax' => 15,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data.amount']);
    }

    public function test_unsupported_provider_returns_422(): void
    {
        $response = $this->authorizedProvider()->getJson('/api/v1/contacts?provider=other');

        $response->assertStatus(422);
    }

    public function test_payphone_status_canceled_maps_to_error(): void
    {
        Http::fake([
            'api.payphone.test/Sale/9' => Http::response([
                'transactionId' => 9,
                'transactionStatus' => 'Canceled',
            ], 200),
        ]);

        $response = $this->authorizedGateway()->getJson('/api/v1/payment-gateways/transactions/9?provider=payphone');

        $response->assertOk()
            ->assertJsonPath('status', 'error');
    }

    public function test_payphone_status_rejected_maps_to_error(): void
    {
        Http::fake([
            'api.payphone.test/Sale/10' => Http::response([
                'transactionId' => 10,
                'transactionStatus' => 'Rejected',
            ], 200),
        ]);

        $response = $this->authorizedGateway()->getJson('/api/v1/payment-gateways/transactions/10?provider=payphone');

        $response->assertOk()
            ->assertJsonPath('status', 'error');
    }

    public function test_payphone_unknown_status_maps_to_error(): void
    {
        Http::fake([
            'api.payphone.test/Sale/11' => Http::response([
                'transactionId' => 11,
                'transactionStatus' => 'Wat',
            ], 200),
        ]);

        $response = $this->authorizedGateway()->getJson('/api/v1/payment-gateways/transactions/11?provider=payphone');

        $response->assertOk()
            ->assertJsonPath('status', 'error');
    }

    public function test_payphone_reversal_failure_maps_to_error(): void
    {
        Http::fake([
            'api.payphone.test/Reverse' => Http::response([
                'status' => 'Failed',
                'message' => 'NOPE',
            ], 200),
        ]);

        $response = $this->authorized()->postJson('/api/v1/payment-gateways/reversals', [
            'provider' => 'payphone',
            'credentials' => ['bearerToken' => 'gw'],
            'data' => [
                'transactionId' => 1,
                'clientTransactionId' => 'c',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'error');
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
            'X-Provider-Api-Key' => 'k',
        ]);
    }

    protected function authorizedGateway(): self
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer secret-token',
            'Accept' => 'application/json',
            'X-Gateway-Bearer' => 'gw',
        ]);
    }
}

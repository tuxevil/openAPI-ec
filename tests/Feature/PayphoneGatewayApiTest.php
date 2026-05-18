<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayphoneGatewayApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'internal_systems.tokens' => ['nexOS' => 'secret-token'],
            'services.payphone.base_url' => 'https://api.payphone.test',
            'services.payphone.timeout' => 30,
        ]);
    }

    public function test_it_requires_gateway_bearer_token_for_get_requests(): void
    {
        $response = $this->authorized()->getJson('/api/v1/payment-gateways/transactions/123?provider=payphone');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['credentials.bearerToken']);
    }

    public function test_it_validates_amount_breakdown_for_sales(): void
    {
        $response = $this->authorized()->postJson('/api/v1/payment-gateways/sales', [
            'provider' => 'payphone',
            'credentials' => ['bearerToken' => 'gateway-token'],
            'data' => [
                'phoneNumber' => '0999999999',
                'countryCode' => '593',
                'reference' => 'Pago orden 123',
                'clientTransactionId' => 'SYS-123',
                'amount' => 120,
                'amountWithTax' => 100,
                'amountWithoutTax' => 0,
                'tax' => 15,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data.amount']);
    }

    public function test_it_creates_a_sale_with_normalized_response(): void
    {
        Http::fake(function (Request $request) {
            $this->assertSame('Bearer gateway-token', $request->header('Authorization')[0] ?? null);
            $this->assertSame('SYS-123', $request['clientTransactionId']);

            return Http::response([
                'transactionId' => 123456789,
            ], 200);
        });

        $response = $this->authorized()->postJson('/api/v1/payment-gateways/sales', [
            'provider' => 'payphone',
            'credentials' => ['bearerToken' => 'gateway-token'],
            'data' => [
                'phoneNumber' => '0999999999',
                'countryCode' => '593',
                'reference' => 'Pago orden 123',
                'clientTransactionId' => 'SYS-123',
                'amount' => 115,
                'amountWithTax' => 100,
                'amountWithoutTax' => 0,
                'tax' => 15,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('provider', 'payphone')
            ->assertJsonPath('operation', 'payment-gateways.sale.create')
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('externalId', '123456789')
            ->assertJsonPath('data.clientTransactionId', 'SYS-123');
    }

    public function test_it_creates_a_payment_link(): void
    {
        Http::fake([
            'https://api.payphone.test/Links' => Http::response([
                'url' => 'https://payp.hn/x/ejemplo123',
            ], 200),
        ]);

        $response = $this->authorized()->postJson('/api/v1/payment-gateways/links', [
            'provider' => 'payphone',
            'credentials' => ['bearerToken' => 'gateway-token'],
            'data' => [
                'reference' => 'Pago orden 123',
                'clientTransactionId' => 'SYS-123',
                'amount' => 115,
                'amountWithTax' => 100,
                'amountWithoutTax' => 0,
                'tax' => 15,
                'notifyUrl' => 'https://nexos.test/webhooks/payphone',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('operation', 'payment-gateways.link.create')
            ->assertJsonPath('data.url', 'https://payp.hn/x/ejemplo123');
    }

    public function test_it_maps_approved_transaction_status_to_success(): void
    {
        Http::fake([
            'https://api.payphone.test/Sale/123456789' => Http::response([
                'transactionId' => 123456789,
                'clientTransactionId' => 'SYS-123',
                'transactionStatus' => 'Approved',
                'amount' => 115,
                'currency' => 'USD',
            ], 200),
        ]);

        $response = $this->authorizedGateway()->getJson('/api/v1/payment-gateways/transactions/123456789?provider=payphone');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.transactionStatus', 'Approved');
    }

    public function test_it_maps_pending_transaction_status_to_pending(): void
    {
        Http::fake([
            'https://api.payphone.test/Sale/123456789' => Http::response([
                'transactionId' => 123456789,
                'clientTransactionId' => 'SYS-123',
                'transactionStatus' => 'Pending',
                'amount' => 115,
                'currency' => 'USD',
            ], 200),
        ]);

        $response = $this->authorizedGateway()->getJson('/api/v1/payment-gateways/transactions/123456789?provider=payphone');

        $response->assertOk()
            ->assertJsonPath('status', 'pending');
    }

    public function test_it_reverses_a_transaction(): void
    {
        Http::fake(function (Request $request) {
            $this->assertSame(123456789, $request['transactionId']);

            return Http::response([
                'status' => 'Success',
                'message' => 'Transaccion reversada exitosamente',
            ], 200);
        });

        $response = $this->authorized()->postJson('/api/v1/payment-gateways/reversals', [
            'provider' => 'payphone',
            'credentials' => ['bearerToken' => 'gateway-token'],
            'data' => [
                'transactionId' => 123456789,
                'clientTransactionId' => 'SYS-123',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.transactionId', 123456789);
    }

    public function test_it_maps_payphone_timeouts(): void
    {
        Http::fake(function () {
            throw new ConnectionException('timeout');
        });

        $response = $this->authorizedGateway()->getJson('/api/v1/payment-gateways/transactions/123456789?provider=payphone');

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

    protected function authorizedGateway(): self
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer secret-token',
            'Accept' => 'application/json',
            'X-Gateway-Bearer' => 'gateway-token',
        ]);
    }
}

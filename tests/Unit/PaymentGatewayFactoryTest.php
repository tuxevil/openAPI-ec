<?php

namespace Tests\Unit;

use App\Exceptions\UnsupportedProviderException;
use App\Providers\PaymentGateway\PaymentGatewayFactory;
use App\Providers\PaymentGateway\Payphone\PayphoneProvider;
use App\ValueObjects\GatewayCredentials;
use PHPUnit\Framework\TestCase;

class PaymentGatewayFactoryTest extends TestCase
{
    public function test_it_resolves_payphone_provider(): void
    {
        $provider = (new PaymentGatewayFactory())->make('payphone', new GatewayCredentials('gateway-token'));

        $this->assertInstanceOf(PayphoneProvider::class, $provider);
    }

    public function test_it_rejects_unknown_gateway_provider(): void
    {
        $this->expectException(UnsupportedProviderException::class);

        (new PaymentGatewayFactory())->make('unknown', new GatewayCredentials('gateway-token'));
    }
}

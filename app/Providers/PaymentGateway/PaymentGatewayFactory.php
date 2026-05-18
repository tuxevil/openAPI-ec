<?php

namespace App\Providers\PaymentGateway;

use App\Contracts\PaymentGatewayProvider;
use App\Exceptions\UnsupportedProviderException;
use App\Providers\PaymentGateway\Payphone\PayphoneClient;
use App\Providers\PaymentGateway\Payphone\PayphoneProvider;
use App\ValueObjects\GatewayCredentials;

class PaymentGatewayFactory
{
    public function make(string $provider, GatewayCredentials $credentials): PaymentGatewayProvider
    {
        return match (strtolower($provider)) {
            'payphone' => new PayphoneProvider(new PayphoneClient($credentials)),
            default => throw UnsupportedProviderException::for($provider),
        };
    }
}

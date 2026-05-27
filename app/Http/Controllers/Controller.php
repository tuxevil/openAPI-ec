<?php

namespace App\Http\Controllers;

use App\Contracts\AccountingProvider;
use App\Contracts\PaymentGatewayProvider;
use App\Providers\Accounting\ProviderFactory;
use App\Providers\PaymentGateway\PaymentGatewayFactory;
use App\ValueObjects\GatewayCredentials;
use App\ValueObjects\ProviderCredentials;

abstract class Controller
{
    protected function provider(ProviderFactory $factory, string $provider, array $credentials): AccountingProvider
    {
        return $factory->make($provider, ProviderCredentials::fromArray($credentials));
    }

    protected function paymentGateway(PaymentGatewayFactory $factory, string $provider, array $credentials): PaymentGatewayProvider
    {
        return $factory->make($provider, GatewayCredentials::fromArray($credentials));
    }
}

<?php

namespace App\Http\Controllers;

use App\Contracts\AccountingProvider;
use App\Providers\Accounting\ProviderFactory;
use App\ValueObjects\ProviderCredentials;

abstract class Controller
{
    protected function provider(ProviderFactory $factory, string $provider, array $credentials): AccountingProvider
    {
        return $factory->make($provider, ProviderCredentials::fromArray($credentials));
    }
}

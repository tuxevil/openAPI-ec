<?php

namespace App\Providers\Accounting;

use App\Contracts\AccountingProvider;
use App\Exceptions\UnsupportedProviderException;
use App\Providers\Accounting\Contifico\ContificoClient;
use App\Providers\Accounting\Contifico\ContificoProvider;
use App\ValueObjects\ProviderCredentials;

class ProviderFactory
{
    public function make(string $provider, ProviderCredentials $credentials): AccountingProvider
    {
        return match (strtolower($provider)) {
            'contifico' => new ContificoProvider(new ContificoClient($credentials)),
            default => throw UnsupportedProviderException::for($provider),
        };
    }
}

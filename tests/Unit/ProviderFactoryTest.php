<?php

namespace Tests\Unit;

use App\Exceptions\UnsupportedProviderException;
use App\Providers\Accounting\Contifico\ContificoProvider;
use App\Providers\Accounting\ProviderFactory;
use App\ValueObjects\ProviderCredentials;
use PHPUnit\Framework\TestCase;

class ProviderFactoryTest extends TestCase
{
    public function test_it_resolves_contifico_provider(): void
    {
        $provider = (new ProviderFactory())->make('contifico', new ProviderCredentials('api-key', 'pos-token'));

        $this->assertInstanceOf(ContificoProvider::class, $provider);
    }

    public function test_it_rejects_unknown_provider(): void
    {
        $this->expectException(UnsupportedProviderException::class);

        (new ProviderFactory())->make('unknown', new ProviderCredentials('api-key'));
    }
}

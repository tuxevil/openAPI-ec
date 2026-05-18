<?php

namespace App\Exceptions;

class UnsupportedProviderException extends ProviderException
{
    public static function for(string $provider): self
    {
        return new self(
            message: sprintf('Provider [%s] is not supported.', $provider),
            httpStatus: 422,
            apiCode: 'provider_not_supported',
            details: ['provider' => $provider],
            provider: $provider,
        );
    }
}

<?php

namespace App\Http\Requests\Api\V1\PaymentGateways;

use App\Http\Requests\Api\V1\ProviderBodyRequest;

class GatewayBodyRequest extends ProviderBodyRequest
{
    protected function allowedProviders(): array
    {
        return ['payphone'];
    }

    protected function credentialRules(): array
    {
        return [
            'credentials.bearerToken' => ['required', 'string'],
        ];
    }
}

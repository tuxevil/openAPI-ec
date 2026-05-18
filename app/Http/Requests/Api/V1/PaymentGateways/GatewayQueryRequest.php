<?php

namespace App\Http\Requests\Api\V1\PaymentGateways;

use App\Http\Requests\Api\V1\ProviderQueryRequest;

class GatewayQueryRequest extends ProviderQueryRequest
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

    protected function headerCredentials(): array
    {
        return [
            'bearerToken' => $this->header('X-Gateway-Bearer'),
        ];
    }
}

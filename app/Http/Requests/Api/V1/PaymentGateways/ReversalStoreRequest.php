<?php

namespace App\Http\Requests\Api\V1\PaymentGateways;

class ReversalStoreRequest extends GatewayBodyRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'data.transactionId' => ['required', 'integer', 'min:1'],
            'data.clientTransactionId' => ['required', 'string', 'max:255'],
        ]);
    }
}

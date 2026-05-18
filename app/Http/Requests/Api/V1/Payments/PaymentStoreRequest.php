<?php

namespace App\Http\Requests\Api\V1\Payments;

use App\Http\Requests\Api\V1\ProviderBodyRequest;

class PaymentStoreRequest extends ProviderBodyRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'data.method' => ['required', 'in:EF,CQ,TC,TRA'],
            'data.amount' => ['required', 'numeric', 'min:0.01'],
            'data.paidAt' => ['nullable', 'date'],
            'data.reference' => ['nullable', 'string', 'max:15'],
            'data.bankAccountId' => ['nullable', 'string', 'max:16'],
            'data.checkNumber' => ['nullable', 'string', 'max:15'],
            'data.cardProcessor' => ['nullable', 'in:D,M,E,P,A'],
        ]);
    }
}

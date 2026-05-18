<?php

namespace App\Http\Requests\Api\V1\PaymentGateways;

use App\Http\Requests\Api\V1\PaymentGateways\Concerns\ValidatesGatewayAmounts;
use Illuminate\Validation\Validator;

class LinkStoreRequest extends GatewayBodyRequest
{
    use ValidatesGatewayAmounts;

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'data.reference' => ['required', 'string', 'max:255'],
            'data.clientTransactionId' => ['required', 'string', 'max:255'],
            'data.amount' => ['required', 'integer', 'min:0'],
            'data.amountWithTax' => ['required', 'integer', 'min:0'],
            'data.amountWithoutTax' => ['required', 'integer', 'min:0'],
            'data.tax' => ['required', 'integer', 'min:0'],
            'data.expireIn' => ['nullable', 'integer', 'min:1'],
            'data.notifyUrl' => ['nullable', 'url'],
            'data.storeId' => ['nullable', 'uuid'],
            'data.terminalId' => ['nullable', 'string', 'max:255'],
            'data.documentId' => ['nullable', 'string', 'max:20'],
            'data.email' => ['nullable', 'email'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateAmountBreakdown($validator);
        });
    }
}

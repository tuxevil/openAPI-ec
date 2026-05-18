<?php

namespace App\Http\Requests\Api\V1\PaymentGateways\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesGatewayAmounts
{
    protected function validateAmountBreakdown(Validator $validator): void
    {
        $amount = (int) $this->input('data.amount', 0);
        $amountWithTax = (int) $this->input('data.amountWithTax', 0);
        $amountWithoutTax = (int) $this->input('data.amountWithoutTax', 0);
        $tax = (int) $this->input('data.tax', 0);

        if ($amount !== $amountWithTax + $amountWithoutTax + $tax) {
            $validator->errors()->add('data.amount', 'The amount must equal amountWithTax + amountWithoutTax + tax.');
        }
    }
}

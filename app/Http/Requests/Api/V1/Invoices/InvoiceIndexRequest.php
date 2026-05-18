<?php

namespace App\Http\Requests\Api\V1\Invoices;

use App\Http\Requests\Api\V1\ProviderQueryRequest;

class InvoiceIndexRequest extends ProviderQueryRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'customerIdentification' => ['nullable', 'string'],
            'issuedFrom' => ['nullable', 'date'],
            'issuedTo' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    public function filters(): array
    {
        return $this->safe()->only(['customerIdentification', 'issuedFrom', 'issuedTo', 'page']);
    }
}

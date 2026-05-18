<?php

namespace App\Http\Requests\Api\V1\Products;

use App\Http\Requests\Api\V1\ProviderQueryRequest;

class ProductIndexRequest extends ProviderQueryRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'in:A,I'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    public function filters(): array
    {
        return $this->safe()->only(['search', 'status', 'page']);
    }
}

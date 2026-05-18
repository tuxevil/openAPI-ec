<?php

namespace App\Http\Requests\Api\V1\Products;

use App\Http\Requests\Api\V1\ProviderBodyRequest;

class ProductUpsertRequest extends ProviderBodyRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->input('data', []);
        $data['status'] = $data['status'] ?? 'A';
        $data['type'] = $data['type'] ?? 'PRO';
        $data['taxRate'] = $data['taxRate'] ?? 15;

        $this->merge(['data' => $data]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'data.code' => ['required', 'string', 'max:200'],
            'data.name' => ['required', 'string', 'max:300'],
            'data.type' => ['required', 'in:PRO,SER'],
            'data.price' => ['required', 'numeric', 'min:0'],
            'data.taxRate' => ['required', 'numeric', 'min:0'],
            'data.status' => ['required', 'in:A,I'],
            'data.stock' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}

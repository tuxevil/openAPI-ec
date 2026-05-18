<?php

namespace App\Http\Requests\Api\V1\Invoices;

use App\Http\Requests\Api\V1\Concerns\ValidatesEcuadorianIdentification;
use App\Http\Requests\Api\V1\ProviderBodyRequest;
use Illuminate\Validation\Validator;

class InvoiceStoreRequest extends ProviderBodyRequest
{
    use ValidatesEcuadorianIdentification;

    protected function prepareForValidation(): void
    {
        $data = $this->input('data', []);
        $data['issuedAt'] = $data['issuedAt'] ?? now()->toDateString();
        $data['status'] = $data['status'] ?? 'P';
        $data['items'] = array_map(function (array $item): array {
            $item['taxRate'] = $item['taxRate'] ?? 15;
            $item['discountPercentage'] = $item['discountPercentage'] ?? 0;

            return $item;
        }, $data['items'] ?? []);

        $this->merge(['data' => $data]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'credentials.posToken' => ['required', 'string'],
            'data.number' => ['required', 'string', 'max:17'],
            'data.issuedAt' => ['required', 'date'],
            'data.authorization' => ['nullable', 'string', 'max:49'],
            'data.description' => ['nullable', 'string'],
            'data.reference' => ['nullable', 'string', 'max:300'],
            'data.customer' => ['required', 'array'],
            'data.customer.name' => ['required', 'string', 'max:300'],
            'data.customer.identification.type' => ['required', 'in:CEDULA,RUC,PASAPORTE,CONSUMIDOR_FINAL'],
            'data.customer.identification.value' => ['required', 'string'],
            'data.customer.email' => ['nullable', 'email'],
            'data.customer.phone' => ['nullable', 'string'],
            'data.customer.address' => ['nullable', 'string'],
            'data.items' => ['required', 'array', 'min:1'],
            'data.items.*.productExternalId' => ['required', 'string'],
            'data.items.*.quantity' => ['required', 'numeric', 'min:0.000001'],
            'data.items.*.unitPrice' => ['required', 'numeric', 'min:0'],
            'data.items.*.taxRate' => ['required', 'numeric', 'min:0'],
            'data.items.*.discountPercentage' => ['required', 'numeric', 'min:0'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateIdentification($validator, 'data.customer.identification.type', 'data.customer.identification.value');
        });
    }
}

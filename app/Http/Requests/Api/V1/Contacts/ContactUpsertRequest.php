<?php

namespace App\Http\Requests\Api\V1\Contacts;

use App\Http\Requests\Api\V1\Concerns\ValidatesEcuadorianIdentification;
use App\Http\Requests\Api\V1\ProviderBodyRequest;
use Illuminate\Validation\Validator;

class ContactUpsertRequest extends ProviderBodyRequest
{
    use ValidatesEcuadorianIdentification;

    protected function prepareForValidation(): void
    {
        $data = $this->input('data', []);
        $data['isCustomer'] = $data['isCustomer'] ?? true;
        $data['isSupplier'] = $data['isSupplier'] ?? false;
        $data['status'] = $data['status'] ?? 'A';

        $this->merge(['data' => $data]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'credentials.posToken' => ['required', 'string'],
            'data.name' => ['required', 'string', 'max:300'],
            'data.commercialName' => ['nullable', 'string', 'max:300'],
            'data.identification.type' => ['required', 'in:CEDULA,RUC,PASAPORTE,CONSUMIDOR_FINAL'],
            'data.identification.value' => ['required', 'string'],
            'data.email' => ['nullable', 'email'],
            'data.phone' => ['nullable', 'string', 'max:50'],
            'data.address' => ['nullable', 'string', 'max:300'],
            'data.isCustomer' => ['required', 'boolean'],
            'data.isSupplier' => ['required', 'boolean'],
            'data.status' => ['required', 'in:A,I'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateIdentification($validator, 'data.identification.type', 'data.identification.value');
        });
    }
}

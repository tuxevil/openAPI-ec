<?php

namespace App\Http\Requests\Api\V1\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesEcuadorianIdentification
{
    protected function validateIdentification(Validator $validator, string $typePath, string $valuePath): void
    {
        $type = data_get($this->validationData(), $typePath);
        $value = (string) data_get($this->validationData(), $valuePath, '');

        if (! is_string($type) || $value === '') {
            return;
        }

        $valid = match ($type) {
            'CEDULA' => preg_match('/^\d{10}$/', $value) === 1,
            'RUC' => preg_match('/^\d{13}$/', $value) === 1,
            'CONSUMIDOR_FINAL' => $value === '9999999999999',
            'PASAPORTE' => preg_match('/^[A-Za-z0-9\-]{3,20}$/', $value) === 1,
            default => false,
        };

        if (! $valid) {
            $validator->errors()->add($valuePath, sprintf('The %s value is invalid for type %s.', $valuePath, $type));
        }
    }
}

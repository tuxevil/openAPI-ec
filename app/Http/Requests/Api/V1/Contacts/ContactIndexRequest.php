<?php

namespace App\Http\Requests\Api\V1\Contacts;

use App\Http\Requests\Api\V1\ProviderQueryRequest;

class ContactIndexRequest extends ProviderQueryRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'search' => ['nullable', 'string'],
            'status' => ['nullable', 'in:A,I'],
            'type' => ['nullable', 'in:N,J,I,P'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    public function filters(): array
    {
        return $this->safe()->only(['search', 'status', 'type', 'page']);
    }
}

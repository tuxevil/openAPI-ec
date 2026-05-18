<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ProviderQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'provider' => $this->query('provider'),
            'credentials' => [
                'apiKey' => $this->header('X-Provider-Api-Key'),
                'posToken' => $this->header('X-Provider-Pos-Token'),
            ],
        ]);
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'in:contifico'],
            'credentials.apiKey' => ['required', 'string'],
            'credentials.posToken' => ['nullable', 'string'],
        ];
    }

    public function provider(): string
    {
        return (string) $this->validated('provider');
    }

    public function credentials(): array
    {
        return $this->validated('credentials');
    }
}

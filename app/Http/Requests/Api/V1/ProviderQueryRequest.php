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
            'credentials' => $this->headerCredentials(),
        ]);
    }

    public function rules(): array
    {
        return array_merge([
            'provider' => ['required', 'in:'.implode(',', $this->allowedProviders())],
        ], $this->credentialRules());
    }

    public function provider(): string
    {
        return (string) $this->validated('provider');
    }

    public function credentials(): array
    {
        return $this->validated('credentials');
    }

    protected function allowedProviders(): array
    {
        return ['contifico'];
    }

    protected function credentialRules(): array
    {
        return [
            'credentials.apiKey' => ['required', 'string'],
            'credentials.posToken' => ['nullable', 'string'],
        ];
    }

    protected function headerCredentials(): array
    {
        return [
            'apiKey' => $this->header('X-Provider-Api-Key'),
            'posToken' => $this->header('X-Provider-Pos-Token'),
        ];
    }
}

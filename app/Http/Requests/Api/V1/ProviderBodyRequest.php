<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ProviderBodyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'provider' => ['required', 'in:'.implode(',', $this->allowedProviders())],
            'data' => ['required', 'array'],
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

    public function payload(): array
    {
        return $this->validated('data');
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
}

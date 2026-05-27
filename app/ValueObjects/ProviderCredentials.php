<?php

namespace App\ValueObjects;

class ProviderCredentials
{
    public function __construct(
        public readonly string $apiKey,
        public readonly ?string $posToken = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            apiKey: (string) ($data['apiKey'] ?? ''),
            posToken: isset($data['posToken']) ? (string) $data['posToken'] : null,
        );
    }
}

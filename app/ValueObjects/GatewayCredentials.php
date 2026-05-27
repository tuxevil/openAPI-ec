<?php

namespace App\ValueObjects;

class GatewayCredentials
{
    public function __construct(
        public readonly string $bearerToken,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            bearerToken: (string) ($data['bearerToken'] ?? ''),
        );
    }
}

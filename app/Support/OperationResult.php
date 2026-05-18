<?php

namespace App\Support;

class OperationResult
{
    public function __construct(
        public readonly string $provider,
        public readonly string $operation,
        public readonly ?string $externalId,
        public readonly string $status,
        public readonly array $data,
        public readonly array $providerResponse,
    ) {
    }
}

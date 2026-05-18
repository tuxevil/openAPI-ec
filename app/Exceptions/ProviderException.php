<?php

namespace App\Exceptions;

use RuntimeException;

class ProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        protected int $httpStatus,
        protected string $apiCode,
        protected array $details = [],
        protected ?string $provider = null,
    ) {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function apiCode(): string
    {
        return $this->apiCode;
    }

    public function details(): array
    {
        return $this->details;
    }

    public function provider(): ?string
    {
        return $this->provider;
    }
}

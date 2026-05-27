<?php

namespace App\Http\Resources;

use App\Support\OperationResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OperationResult */
class OperationResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'provider' => $this->provider,
            'operation' => $this->operation,
            'externalId' => $this->externalId,
            'status' => $this->status,
            'data' => $this->data,
            'providerResponse' => $this->providerResponse,
        ];
    }
}

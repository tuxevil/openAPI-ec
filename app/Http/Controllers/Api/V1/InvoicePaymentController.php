<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payments\PaymentStoreRequest;
use App\Http\Requests\Api\V1\ProviderQueryRequest;
use App\Http\Resources\OperationResultResource;
use App\Providers\Accounting\ProviderFactory;

class InvoicePaymentController extends Controller
{
    public function index(string $invoiceId, ProviderQueryRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->listInvoicePayments($invoiceId)
        );
    }

    public function store(string $invoiceId, PaymentStoreRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->createInvoicePayment($invoiceId, $request->payload())
        );
    }
}

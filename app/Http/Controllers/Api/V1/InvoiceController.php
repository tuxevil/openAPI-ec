<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Invoices\InvoiceIndexRequest;
use App\Http\Requests\Api\V1\Invoices\InvoiceStoreRequest;
use App\Http\Requests\Api\V1\ProviderQueryRequest;
use App\Http\Resources\OperationResultResource;
use App\Providers\Accounting\ProviderFactory;

class InvoiceController extends Controller
{
    public function index(InvoiceIndexRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->listInvoices($request->filters())
        );
    }

    public function show(string $id, ProviderQueryRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->getInvoice($id)
        );
    }

    public function store(InvoiceStoreRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->createInvoice($request->payload())
        );
    }

    public function status(string $id, ProviderQueryRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->getInvoiceStatus($id)
        );
    }
}

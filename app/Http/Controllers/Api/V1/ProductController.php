<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Products\ProductIndexRequest;
use App\Http\Requests\Api\V1\Products\ProductUpsertRequest;
use App\Http\Requests\Api\V1\ProviderQueryRequest;
use App\Http\Resources\OperationResultResource;
use App\Providers\Accounting\ProviderFactory;

class ProductController extends Controller
{
    public function index(ProductIndexRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->listProducts($request->filters())
        );
    }

    public function show(string $id, ProviderQueryRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->getProduct($id)
        );
    }

    public function store(ProductUpsertRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->createProduct($request->payload())
        );
    }

    public function update(string $id, ProductUpsertRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->updateProduct($id, $request->payload())
        );
    }

    public function stock(string $id, ProviderQueryRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->getProductStock($id)
        );
    }
}

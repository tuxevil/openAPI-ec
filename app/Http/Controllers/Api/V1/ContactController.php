<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Contacts\ContactIndexRequest;
use App\Http\Requests\Api\V1\Contacts\ContactUpsertRequest;
use App\Http\Requests\Api\V1\ProviderQueryRequest;
use App\Http\Resources\OperationResultResource;
use App\Providers\Accounting\ProviderFactory;

class ContactController extends Controller
{
    public function index(ContactIndexRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->listContacts($request->filters())
        );
    }

    public function show(string $id, ProviderQueryRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->getContact($id)
        );
    }

    public function store(ContactUpsertRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->createContact($request->payload())
        );
    }

    public function update(string $id, ContactUpsertRequest $request, ProviderFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->provider($factory, $request->provider(), $request->credentials())->updateContact($id, $request->payload())
        );
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaymentGateways\GatewayQueryRequest;
use App\Http\Requests\Api\V1\PaymentGateways\LinkStoreRequest;
use App\Http\Requests\Api\V1\PaymentGateways\ReversalStoreRequest;
use App\Http\Requests\Api\V1\PaymentGateways\SaleStoreRequest;
use App\Http\Resources\OperationResultResource;
use App\Providers\PaymentGateway\PaymentGatewayFactory;

class PaymentGatewayController extends Controller
{
    public function storeSale(SaleStoreRequest $request, PaymentGatewayFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->paymentGateway($factory, $request->provider(), $request->credentials())->createSale($request->payload())
        );
    }

    public function storeLink(LinkStoreRequest $request, PaymentGatewayFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->paymentGateway($factory, $request->provider(), $request->credentials())->createPaymentLink($request->payload())
        );
    }

    public function showTransaction(string $transactionId, GatewayQueryRequest $request, PaymentGatewayFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->paymentGateway($factory, $request->provider(), $request->credentials())->getTransactionStatus($transactionId)
        );
    }

    public function storeReversal(ReversalStoreRequest $request, PaymentGatewayFactory $factory): OperationResultResource
    {
        return new OperationResultResource(
            $this->paymentGateway($factory, $request->provider(), $request->credentials())->reverseTransaction($request->payload())
        );
    }
}

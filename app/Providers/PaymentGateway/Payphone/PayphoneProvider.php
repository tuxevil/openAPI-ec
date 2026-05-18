<?php

namespace App\Providers\PaymentGateway\Payphone;

use App\Contracts\PaymentGatewayProvider;
use App\Support\OperationResult;

class PayphoneProvider implements PaymentGatewayProvider
{
    public function __construct(protected PayphoneClient $client)
    {
    }

    public function createSale(array $data): OperationResult
    {
        $response = $this->client->post('/Sale', PayphoneNormalizer::salePayload($data));

        return new OperationResult(
            'payphone',
            'payment-gateways.sale.create',
            isset($response['transactionId']) ? (string) $response['transactionId'] : null,
            'pending',
            PayphoneNormalizer::sale($response, $data),
            $response,
        );
    }

    public function createPaymentLink(array $data): OperationResult
    {
        $response = $this->client->post('/Links', PayphoneNormalizer::linkPayload($data));

        return new OperationResult(
            'payphone',
            'payment-gateways.link.create',
            $data['clientTransactionId'] ?? null,
            'pending',
            PayphoneNormalizer::link($response, $data),
            $response,
        );
    }

    public function getTransactionStatus(string $transactionId): OperationResult
    {
        $response = $this->client->get("/Sale/{$transactionId}");
        $data = PayphoneNormalizer::transaction($response);

        return new OperationResult(
            'payphone',
            'payment-gateways.transaction.get',
            (string) ($response['transactionId'] ?? $transactionId),
            PayphoneNormalizer::operationStatusFromTransaction((string) ($response['transactionStatus'] ?? '')),
            $data,
            $response,
        );
    }

    public function reverseTransaction(array $data): OperationResult
    {
        $response = $this->client->post('/Reverse', PayphoneNormalizer::reversalPayload($data));

        return new OperationResult(
            'payphone',
            'payment-gateways.reversal.create',
            isset($data['transactionId']) ? (string) $data['transactionId'] : null,
            PayphoneNormalizer::operationStatusFromReverse($response),
            PayphoneNormalizer::reversal($response, $data),
            $response,
        );
    }
}

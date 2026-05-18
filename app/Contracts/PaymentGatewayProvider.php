<?php

namespace App\Contracts;

use App\Support\OperationResult;

interface PaymentGatewayProvider
{
    public function createSale(array $data): OperationResult;

    public function createPaymentLink(array $data): OperationResult;

    public function getTransactionStatus(string $transactionId): OperationResult;

    public function reverseTransaction(array $data): OperationResult;
}

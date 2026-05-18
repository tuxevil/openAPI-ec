<?php

namespace App\Contracts;

use App\Support\OperationResult;

interface PaymentsProvider
{
    public function listInvoicePayments(string $invoiceId): OperationResult;

    public function createInvoicePayment(string $invoiceId, array $data): OperationResult;
}

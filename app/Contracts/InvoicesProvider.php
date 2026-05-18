<?php

namespace App\Contracts;

use App\Support\OperationResult;

interface InvoicesProvider
{
    public function listInvoices(array $filters): OperationResult;

    public function getInvoice(string $id): OperationResult;

    public function createInvoice(array $data): OperationResult;

    public function getInvoiceStatus(string $id): OperationResult;
}

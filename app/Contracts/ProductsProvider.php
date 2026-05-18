<?php

namespace App\Contracts;

use App\Support\OperationResult;

interface ProductsProvider
{
    public function listProducts(array $filters): OperationResult;

    public function getProduct(string $id): OperationResult;

    public function createProduct(array $data): OperationResult;

    public function updateProduct(string $id, array $data): OperationResult;

    public function getProductStock(string $id): OperationResult;
}

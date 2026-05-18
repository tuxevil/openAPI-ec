<?php

namespace App\Contracts;

use App\Support\OperationResult;

interface ContactsProvider
{
    public function listContacts(array $filters): OperationResult;

    public function getContact(string $id): OperationResult;

    public function createContact(array $data): OperationResult;

    public function updateContact(string $id, array $data): OperationResult;
}
